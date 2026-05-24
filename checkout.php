<?php
$pageTitle = 'Checkout - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php?redirect=checkout.php');
}

if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Get customer addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_primary DESC");
$stmt->execute([$_SESSION['customer_id']]);
$addresses = $stmt->fetchAll();

// Get store location
$storeLat = getSetting($pdo, 'store_latitude');
$storeLng = getSetting($pdo, 'store_longitude');
$maxRadius = getSetting($pdo, 'max_delivery_radius');
$minPreorderDays = getSetting($pdo, 'min_preorder_days');
$originCityId = getSetting($pdo, 'origin_city_id') ?: '';
$originCityName = getSetting($pdo, 'origin_city_name') ?: '';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressId = $_POST['address_id'] ?? 0;
    $deliveryMethod = $_POST['delivery_method'] ?? '';
    $deliveryDate = $_POST['delivery_date'] ?? '';
    $deliveryTime = $_POST['delivery_time'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $selectedCourier = $_POST['selected_courier'] ?? null;
    $selectedService = $_POST['selected_service'] ?? null;
    
    // Untuk ekspedisi, delivery_date tidak wajib
    if ($deliveryMethod === 'ekspedisi' && !$deliveryDate) {
        $deliveryDate = date('Y-m-d', strtotime('+5 days')); // estimasi default
    }

    // Validation
    if (!$addressId || !$deliveryMethod || !$paymentMethod) {
        $error = 'Semua field harus diisi';
    } elseif ($deliveryMethod !== 'ekspedisi' && !$deliveryDate) {
        $error = 'Pilih tanggal pengiriman';
    } elseif ($deliveryMethod === 'ekspedisi' && (!$selectedCourier || !$selectedService)) {
        $error = 'Pilih layanan ekspedisi terlebih dahulu';
    } else {
        // Get address
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $_SESSION['customer_id']]);
        $address = $stmt->fetch();
        
        if (!$address) {
            $error = 'Alamat tidak valid';
        } else {
            // Find nearest outlet and calculate distance
            $nearestOutlet = findNearestOutlet($pdo, $address['latitude'], $address['longitude']);
            
            if (!$nearestOutlet) {
                $error = 'Tidak ada outlet aktif. Silakan hubungi admin.';
            } else {
                $distance = $nearestOutlet['distance'];
                
                // Check delivery method availability
                if ($deliveryMethod === 'kurir_toko' && $distance > $maxRadius) {
                    $error = 'Alamat terlalu jauh untuk pengiriman kurir toko';
                } elseif ($deliveryMethod === 'kurir_toko' && !$deliveryTime) {
                    $error = 'Pilih waktu pengiriman untuk kurir toko';
                } elseif ($deliveryMethod === 'pick_up' && !$deliveryTime) {
                    $error = 'Pilih waktu pick up';
                } elseif ($deliveryMethod === 'ekspedisi' && $paymentMethod === 'cod') {
                    $error = 'COD tidak tersedia untuk ekspedisi';
                } else {
                    // Check quota (skip untuk ekspedisi)
                    $quota = ['available' => true];
                    if ($deliveryMethod !== 'ekspedisi') {
                        $quota = checkDeliveryQuota($pdo, $deliveryDate, $deliveryMethod);
                    }
                    if (!$quota['available']) {
                        $error = 'Kuota pengiriman untuk tanggal ini sudah penuh';
                    } else {
                        // Calculate totals
                        $subtotal = getCartTotal();
                        $totalWeight = getCartTotalWeight($pdo);
                        
                        // For ekspedisi, use cost from POST (calculated by RajaOngkir on frontend)
                        if ($deliveryMethod === 'ekspedisi' && !empty($_POST['shipping_cost_value'])) {
                            $shippingCost = intval($_POST['shipping_cost_value']);
                        } else {
                            $shippingCost = calculateShippingCost($pdo, $deliveryMethod, $distance, $totalWeight);
                        }
                        
                        $total = $subtotal + $shippingCost;
                        
                        // Create order
                        try {
                            $pdo->beginTransaction();
                            
                            $orderNumber = generateOrderNumber();
                            $stmt = $pdo->prepare("
                                INSERT INTO orders (order_number, user_id, address_id, delivery_method, delivery_date, 
                                                  delivery_time, distance, subtotal, shipping_cost, total, payment_method, notes, courier, courier_service)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $orderNumber, $_SESSION['customer_id'], $addressId, $deliveryMethod, $deliveryDate,
                                $deliveryTime, $distance, $subtotal, $shippingCost, $total, $paymentMethod, $notes,
                                $selectedCourier ?: null, $selectedService ?: null
                            ]);
                            
                            $orderId = $pdo->lastInsertId();
                            
                            // Insert order items
                            $stmt = $pdo->prepare("
                                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            
                            foreach ($_SESSION['cart'] as $item) {
                                $itemSubtotal = $item['price'] * $item['quantity'];
                                $stmt->execute([
                                    $orderId, $item['product_id'], $item['product_name'],
                                    $item['price'], $item['quantity'], $itemSubtotal
                                ]);
                                
                                // Reduce product stock
                                $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                                $stmtStock->execute([$item['quantity'], $item['product_id']]);
                            }
                            
                            // Update quota
                            updateDeliveryQuota($pdo, $deliveryDate, $deliveryMethod);
                            
                            // COD: langsung diproses
                            if ($paymentMethod === 'cod') {
                                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'diproses' WHERE id = ?");
                                $stmt->execute([$orderId]);
                            }
                            
                            $pdo->commit();
                            
                            // Clear cart
                            unset($_SESSION['cart']);
                            
                            // Midtrans: ke halaman pembayaran, COD: langsung sukses
                            if ($paymentMethod === 'midtrans') {
                                redirect('payment.php?order=' . $orderNumber);
                            } else {
                                redirect('order_success.php?order=' . $orderNumber);
                            }
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error = 'Terjadi kesalahan: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}
?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">Checkout</h2>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (empty($addresses)): ?>
    <div class="alert alert-warning">
        Anda belum memiliki alamat. <a href="addresses.php">Tambah alamat sekarang</a>
    </div>
    <?php else: ?>
    
    <form method="POST" id="checkoutForm">
        <input type="hidden" name="selected_courier" id="selectedCourier">
        <input type="hidden" name="selected_service" id="selectedService">
        <input type="hidden" name="shipping_cost_value" id="shippingCostValue">
        <div class="row">
            <div class="col-md-8">
                <!-- Address Selection -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Pilih Alamat Pengiriman</h5>
                        <?php foreach ($addresses as $addr): ?>
                        <div class="address-card <?php echo $addr['is_primary'] ? 'primary' : ''; ?>">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="address_id" 
                                       value="<?php echo $addr['id']; ?>" 
                                       data-lat="<?php echo $addr['latitude']; ?>"
                                       data-lng="<?php echo $addr['longitude']; ?>"
                                       data-city-id="<?php echo $addr['city_id'] ?? ''; ?>"
                                       data-city-name="<?php echo htmlspecialchars($addr['city_name'] ?? ''); ?>"
                                       <?php echo $addr['is_primary'] ? 'checked' : ''; ?> required>
                                <label class="form-check-label">
                                    <strong><?php echo htmlspecialchars($addr['label']); ?></strong>
                                    <?php if ($addr['is_primary']): ?>
                                    <span class="badge bg-primary ms-2">Utama</span>
                                    <?php endif; ?>
                                    <br>
                                    <?php echo htmlspecialchars($addr['recipient_name']); ?> - <?php echo htmlspecialchars($addr['recipient_phone']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($addr['address']); ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <a href="addresses.php" class="btn btn-outline-primary btn-sm mt-2">Tambah Alamat Baru</a>
                    </div>
                </div>
                
                <!-- Delivery Method -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Metode Pengiriman</h5>
                        <div id="deliveryMethods">
                            <p class="text-muted">Pilih alamat terlebih dahulu</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Schedule -->
                <div class="card mb-3" id="scheduleCard" style="display:none;">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3" id="scheduleTitle">Jadwal Pengiriman</h5>
                        <div class="mb-3" id="deliveryDateContainer">
                            <label class="form-label" id="deliveryDateLabel">Pilih Tanggal Pengiriman</label>
                            <select name="delivery_date" class="form-select" id="deliveryDate">
                                <option value="">Pilih tanggal</option>
                            </select>
                            <small class="text-muted" id="preorderNote">Pre-order minimal H+<?php echo $minPreorderDays; ?> dari hari ini</small>
                        </div>
                        <!-- Info ekspedisi (ganti tanggal) -->
                        <div id="ekspedisiScheduleInfo" style="display:none;">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> Estimasi pengiriman <strong>PO 4-7 hari kerja</strong> setelah pesanan dikonfirmasi
                            </div>
                        </div>
                        <div class="mb-3" id="timeSlotContainer" style="display:none;">
                            <label class="form-label" id="timeSlotLabel">Pilih Waktu Pengiriman</label>
                            <select name="delivery_time" class="form-select" id="deliveryTime">
                                <option value="">Pilih waktu</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Metode Pembayaran</h5>
                        <style>
                            .pay-option { display: none; }
                            .pay-option + label {
                                display: flex; align-items: center; gap: 14px;
                                border: 2px solid #e8e8e8; border-radius: 10px;
                                padding: 14px 16px; cursor: pointer; margin-bottom: 10px;
                                transition: border-color .2s, background .2s;
                                background: #fff;
                            }
                            .pay-option:checked + label {
                                border-color: #FF69B4; background: #fff5f9;
                            }
                            .pay-option + label .pay-icon {
                                width: 44px; height: 44px; border-radius: 10px;
                                display: flex; align-items: center; justify-content: center;
                                font-size: 20px; flex-shrink: 0;
                            }
                            .pay-option + label .pay-info { flex: 1; }
                            .pay-option + label .pay-info strong { font-size: 14px; display: block; }
                            .pay-option + label .pay-info small { color: #888; font-size: 12px; }
                            .pay-option + label .pay-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
                            .pay-badge { background: #f0f0f0; border-radius: 4px; padding: 2px 7px; font-size: 10px; font-weight: 600; color: #555; }
                            .pay-option + label .pay-check {
                                width: 20px; height: 20px; border-radius: 50%;
                                border: 2px solid #ccc; flex-shrink: 0;
                                display: flex; align-items: center; justify-content: center;
                                transition: border-color .2s;
                            }
                            .pay-option:checked + label .pay-check {
                                border-color: #FF69B4; background: #FF69B4;
                            }
                            .pay-option:checked + label .pay-check::after {
                                content: ''; width: 8px; height: 8px;
                                background: #fff; border-radius: 50%;
                            }
                        </style>

                        <div id="paymentMethods">
                            <input class="pay-option" type="radio" name="payment_method" value="midtrans" id="payMidtrans" required>
                            <label for="payMidtrans">
                                <div class="pay-icon" style="background:#fff0f7;">
                                    <i class="fas fa-credit-card" style="color:#FF69B4;"></i>
                                </div>
                                <div class="pay-info">
                                    <strong>Bayar Online</strong>
                                    <small>Pilih metode pembayaran di langkah berikutnya</small>
                                    <div class="pay-badges">
                                        <span class="pay-badge">QRIS</span>
                                        <span class="pay-badge">Transfer Bank</span>
                                        <span class="pay-badge">GoPay</span>
                                        <span class="pay-badge">OVO</span>
                                        <span class="pay-badge">DANA</span>
                                        <span class="pay-badge">Kartu Kredit</span>
                                    </div>
                                </div>
                                <div class="pay-check"></div>
                            </label>

                            <div id="codOption">
                                <input class="pay-option" type="radio" name="payment_method" value="cod" id="payCod">
                                <label for="payCod">
                                    <div class="pay-icon" style="background:#f0fff4;">
                                        <i class="fas fa-money-bill-wave" style="color:#28a745;"></i>
                                    </div>
                                    <div class="pay-info">
                                        <strong>COD (Cash on Delivery)</strong>
                                        <small>Bayar tunai saat pesanan tiba di tangan kamu</small>
                                    </div>
                                    <div class="pay-check"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Catatan (Opsional)</h5>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan untuk pesanan Anda"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Ringkasan Pesanan</h5>
                        <?php
                        // Subtotal harga normal (sebelum diskon)
                        $subtotalNormal = 0;
                        $totalDiscount = 0;
                        foreach ($_SESSION['cart'] as $item) {
                            $origPrice = !empty($item['original_price']) ? $item['original_price'] : $item['price'];
                            $subtotalNormal += $origPrice * $item['quantity'];
                            if ($origPrice > $item['price']) {
                                $totalDiscount += ($origPrice - $item['price']) * $item['quantity'];
                            }
                        }
                        $subtotal = getCartTotal(); // harga setelah diskon (untuk kalkulasi total)
                        ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?php echo formatRupiah($subtotalNormal); ?></span>
                        </div>
                        <?php if ($totalDiscount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span><i class="fas fa-tag"></i> Diskon</span>
                            <span>- <?php echo formatRupiah($totalDiscount); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkir</span>
                            <span id="shippingCost">Rp 0</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong class="text-primary" id="totalAmount"><?php echo formatRupiah($subtotalNormal - $totalDiscount); ?></strong>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Buat Pesanan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<script>
// Parse PHP variables safely
const storeLat = parseFloat('<?php echo $storeLat ?: "-6.200000"; ?>');
const storeLng = parseFloat('<?php echo $storeLng ?: "106.816666"; ?>');
const maxRadius = parseInt('<?php echo $maxRadius ?: "10"; ?>');
const minPreorderDays = parseInt('<?php echo $minPreorderDays ?: "2"; ?>');
const subtotal = parseInt('<?php echo ($subtotalNormal - $totalDiscount) ?: "0"; ?>');
const totalDiscount = parseInt('<?php echo $totalDiscount ?? "0"; ?>');
const subtotalAfterDiscount = subtotal;
const originCityId = '<?php echo addslashes($originCityId); ?>';
const originCityName = '<?php echo addslashes($originCityName); ?>';
const siteUrl = <?php echo json_encode(SITE_URL); ?>;

console.log('Checkout initialized:', {
    storeLat: storeLat,
    storeLng: storeLng,
    maxRadius: maxRadius,
    minPreorderDays: minPreorderDays,
    subtotal: subtotal,
    siteUrl: siteUrl
});

// Generate delivery dates
function generateDeliveryDates() {
    const select = document.getElementById('deliveryDate');
    const today = new Date();
    
    // Indonesian day names
    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    // Generate next 14 days starting from minPreorderDays
    for (let i = minPreorderDays; i < minPreorderDays + 14; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        
        const dayName = dayNames[date.getDay()];
        const day = date.getDate();
        const month = monthNames[date.getMonth()];
        const year = date.getFullYear();
        
        // Format: YYYY-MM-DD for value
        const dateValue = date.toISOString().split('T')[0];
        
        // Format: Senin, 27 Februari 2026 for display
        const dateDisplay = `${dayName}, ${day} ${month} ${year}`;
        
        const option = document.createElement('option');
        option.value = dateValue;
        option.textContent = dateDisplay;
        select.appendChild(option);
    }
}

// Initialize delivery dates on page load
generateDeliveryDates();

// Handle address selection
document.querySelectorAll('input[name="address_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const lat = parseFloat(this.dataset.lat);
        const lng = parseFloat(this.dataset.lng);
        const cityId = this.dataset.cityId || '';
        const cityName = this.dataset.cityName || '';
        updateDeliveryMethods(lat, lng, cityId, cityName);
    });
});

// Initialize with primary address on page load
window.addEventListener('DOMContentLoaded', function() {
    const primaryAddress = document.querySelector('input[name="address_id"]:checked');
    if (primaryAddress) {
        const lat = parseFloat(primaryAddress.dataset.lat);
        const lng = parseFloat(primaryAddress.dataset.lng);
        const cityId = primaryAddress.dataset.cityId || '';
        const cityName = primaryAddress.dataset.cityName || '';
        updateDeliveryMethods(lat, lng, cityId, cityName);
    } else {
        document.getElementById('deliveryMethods').innerHTML = 
            '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Pilih alamat terlebih dahulu</div>';
    }
});

function updateDeliveryMethods(lat, lng, cityId, cityName) {
    const container = document.getElementById('deliveryMethods');
    if (!container) return;
    container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat metode pengiriman...</p>';
    doUpdateDeliveryMethods(lat, lng, cityId, cityName);
}

function doUpdateDeliveryMethods(lat, lng, cityId, cityName) {
    const container = document.getElementById('deliveryMethods');

    // Kalau koordinat 0,0 — coba geocode dulu via address_id
    if (!lat || !lng || isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
        const addrRadio = document.querySelector('input[name="address_id"]:checked');
        const addrId = addrRadio ? addrRadio.value : 0;
        if (addrId) {
            container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Mendeteksi lokasi...</p>';
            const fd = new FormData();
            fd.append('address_id', addrId);
            fetch(siteUrl + '/api/geocode_address.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    const newLat = parseFloat(d.lat || 0);
                    const newLng = parseFloat(d.lng || 0);
                    if (newLat && newLng && !(newLat === 0 && newLng === 0)) {
                        doUpdateDeliveryMethods(newLat, newLng, cityId, cityName);
                    } else {
                        renderAllMethods(container, cityId, cityName);
                    }
                })
                .catch(() => renderAllMethods(container, cityId, cityName));
        } else {
            renderAllMethods(container, cityId, cityName);
        }
        return;
    }

    // Get nearest outlet first
    fetch(siteUrl + '/api/get_nearest_outlet.php?lat=' + lat + '&lng=' + lng)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div class="alert alert-danger">Tidak ada outlet aktif. Silakan hubungi admin.</div>';
                return;
            }
            
            const outlet = data.outlet;
            const distance = outlet.distance;
            let html = '';
            
            if (distance <= maxRadius) {
                // Jarak dekat: Kurir Toko + Pick Up
                html += buildMethodCard('kurir_toko', 'kurirToko', '<i class="fas fa-motorcycle"></i> Kurir Toko', 'Jarak: ' + distance.toFixed(2) + ' km dari outlet', 'Memuat...', 'text-primary', true);
                html += buildMethodCard('pick_up', 'pickUp', '<i class="fas fa-store"></i> Pick Up', 'Ambil di: ' + outlet.name, 'Gratis', 'text-success', false);
                container.innerHTML = html;
                
                // Fetch kurir toko cost
                fetch(siteUrl + '/api/calculate_shipping.php?method=kurir_toko&distance=' + distance)
                    .then(r => r.json()).then(d => {
                        const el = document.getElementById('kurirTokoCost');
                        if (el && d.success) {
                            el.textContent = formatRupiah(d.shipping_cost);
                            document.getElementById('kurirToko').dataset.cost = d.shipping_cost;
                        }
                    });
            } else {
                // Jarak jauh: Ekspedisi + Pick Up
                if (cityId) {
                    html += '<div id="ekspedisiOptions"><p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat pilihan ekspedisi...</p></div>';
                } else {
                    html += '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Alamat tidak memiliki data kota. <a href="addresses.php">Update alamat</a> untuk melihat ongkir ekspedisi.</div>';
                }
                html += buildMethodCard('pick_up', 'pickUp', '<i class="fas fa-store"></i> Pick Up', 'Ambil di: ' + outlet.name, 'Gratis', 'text-success', false);
                container.innerHTML = html;
                
                if (cityId) {
                    loadEkspedisiOptions(cityId, cityName, outlet);
                }
            }
            
            attachDeliveryListeners(distance, cityId, outlet);
        })
        .catch(() => {
            container.innerHTML = '<div class="alert alert-danger">Gagal memuat metode pengiriman. Silakan refresh halaman.</div>';
        });
}

function renderAllMethods(container, cityId, cityName) {
    // Koordinat tidak tersedia — gunakan city_id untuk tentukan dekat/jauh
    // Ambil outlet dengan city_id untuk perbandingan
    fetch(siteUrl + '/api/get_nearest_outlet.php?lat=0&lng=0')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div class="alert alert-danger">Tidak ada outlet aktif.</div>';
                return;
            }
            const outlet = data.outlet;
            const outletCityId = outlet.city_id ? String(outlet.city_id) : '';
            const customerCityId = cityId ? String(cityId) : '';
            // Sama kota = dekat (kurir toko), beda kota = jauh (ekspedisi)
            const isNearby = outletCityId && customerCityId && (outletCityId === customerCityId);

            let html = '';
            if (isNearby) {
                // Dekat: kurir toko + pick up
                html += buildMethodCard('kurir_toko', 'kurirToko', '<i class="fas fa-motorcycle"></i> Kurir Toko', 'Pengiriman ke alamat Anda', 'Memuat...', 'text-primary', false);
                html += buildMethodCard('pick_up', 'pickUp', '<i class="fas fa-store"></i> Pick Up', 'Ambil di: ' + outlet.name, 'Gratis', 'text-success', false);
                container.innerHTML = html;
                fetch(siteUrl + '/api/calculate_shipping.php?method=kurir_toko&distance=1')
                    .then(r => r.json()).then(d => {
                        const el = document.getElementById('kurirTokoCost');
                        if (el && d.success) { el.textContent = formatRupiah(d.shipping_cost); document.getElementById('kurirToko').dataset.cost = d.shipping_cost; }
                    });
            } else {
                // Jauh: ekspedisi + pick up
                if (cityId) {
                    html += '<div id="ekspedisiOptions"><p class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Memuat pilihan ekspedisi...</p></div>';
                } else {
                    html += '<div class="alert alert-warning small"><i class="fas fa-exclamation-triangle"></i> Tambahkan kota di <a href="addresses.php">alamat</a> untuk ekspedisi</div>';
                }
                html += buildMethodCard('pick_up', 'pickUp', '<i class="fas fa-store"></i> Pick Up', 'Ambil di: ' + outlet.name, 'Gratis', 'text-success', false);
                container.innerHTML = html;
                if (cityId) loadEkspedisiOptions(cityId, cityName, outlet);
            }
            attachDeliveryListeners(isNearby ? 0 : 999, cityId, outlet);
        })
        .catch(() => {
            container.innerHTML = '<div class="alert alert-danger">Gagal memuat metode pengiriman.</div>';
        });
}

function buildMethodCard(value, id, title, subtitle, costText, costClass, required) {
    return '<div class="form-check mb-3 p-3 border rounded">'
        + '<input class="form-check-input" type="radio" name="delivery_method" value="' + value + '" data-cost="0" id="' + id + '"' + (required ? ' required' : '') + '>'
        + '<label class="form-check-label w-100" for="' + id + '">'
        + '<div class="d-flex justify-content-between align-items-center">'
        + '<div><strong>' + title + '</strong><br><small class="text-muted">' + subtitle + '</small></div>'
        + '<strong class="' + costClass + '" id="' + id + 'Cost">' + costText + '</strong>'
        + '</div></label></div>';
}

function loadEkspedisiOptions(cityId, cityName, outlet) {
    const origin = outlet.city_id || '';
    if (!origin) {
        document.getElementById('ekspedisiOptions').innerHTML = 
            '<div class="alert alert-info small"><i class="fas fa-info-circle me-1"></i> Ekspedisi belum tersedia. Pastikan outlet memiliki data kota.</div>';
        return;
    }

    // Fetch weight from server then get ongkir
    fetch(siteUrl + '/api/calculate_shipping.php?method=get_weight')
        .then(r => r.json())
        .then(weightData => {
            const weightGram = Math.max(1000, Math.ceil((weightData.weight || 1) * 1000));
            fetchEkspedisiCosts(origin, cityId, weightGram);
        })
        .catch(() => fetchEkspedisiCosts(origin, cityId, 1000));
}

function fetchEkspedisiCosts(originCityId, destCityId, weightGram) {
    const couriers = ['jne', 'jnt', 'tiki', 'pos'];
    const promises = couriers.map(courier => {
        const fd = new FormData();
        fd.append('action', 'cost');
        fd.append('origin', originCityId);
        fd.append('destination', destCityId);
        fd.append('weight', weightGram);
        fd.append('courier', courier);
        return fetch(siteUrl + '/api/rajaongkir.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .catch(() => ({ success: false }));
    });

    Promise.all(promises).then(results => {
        let allServices = [];
        results.forEach(r => {
            if (r.success && r.services) allServices = allServices.concat(r.services);
        });

        const container = document.getElementById('ekspedisiOptions');
        if (!allServices.length) {
            container.innerHTML = '<div class="alert alert-warning">Ongkir ekspedisi tidak tersedia untuk rute ini.</div>';
            return;
        }

        // Cari rekomendasi: JNE atau JNT yang termurah
        const recommended = allServices
            .filter(s => s.courier === 'JNE' || s.courier === 'JNT')
            .sort((a, b) => a.cost - b.cost)[0];

        const others = allServices.filter(s => s !== recommended);

        let html = '<div class="mb-2"><small class="text-muted fw-bold">Pilih Layanan Ekspedisi:</small></div>';

        // Rekomendasi
        if (recommended) {
            const id = 'ekspedisi_rec';
            html += '<div class="form-check mb-2 p-3 border border-primary rounded bg-light">'
                + '<input class="form-check-input" type="radio" name="delivery_method" value="ekspedisi" '
                + 'data-cost="' + recommended.cost + '" data-courier="' + recommended.courier + '" data-service="' + recommended.service + '" id="' + id + '">'
                + '<label class="form-check-label w-100" for="' + id + '">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<div><span class="badge bg-primary me-1">Rekomendasi</span><strong><i class="fas fa-truck"></i> ' + recommended.courier + ' ' + recommended.service + '</strong>'
                + '<br><small class="text-muted">' + recommended.description + ' &bull; Est. ' + (recommended.etd || '-') + ' hari</small></div>'
                + '<strong class="text-primary">' + formatRupiah(recommended.cost) + '</strong>'
                + '</div></label></div>';
        }

        // Lainnya (collapsed)
        if (others.length) {
            html += '<div id="otherEkspedisiWrap" style="display:none;">';
            others.forEach((svc, i) => {
                const id = 'ekspedisi_' + i;
                html += '<div class="form-check mb-2 p-3 border rounded">'
                    + '<input class="form-check-input" type="radio" name="delivery_method" value="ekspedisi" '
                    + 'data-cost="' + svc.cost + '" data-courier="' + svc.courier + '" data-service="' + svc.service + '" id="' + id + '">'
                    + '<label class="form-check-label w-100" for="' + id + '">'
                    + '<div class="d-flex justify-content-between align-items-center">'
                    + '<div><strong><i class="fas fa-truck"></i> ' + svc.courier + ' ' + svc.service + '</strong>'
                    + '<br><small class="text-muted">' + svc.description + ' &bull; Est. ' + (svc.etd || '-') + ' hari</small></div>'
                    + '<strong class="text-primary">' + formatRupiah(svc.cost) + '</strong>'
                    + '</div></label></div>';
            });
            html += '</div>';
            html += '<button type="button" class="btn btn-link btn-sm p-0 mt-1" onclick="toggleOtherEkspedisi(this)">'
                + '<i class="fas fa-chevron-down"></i> Lihat ekspedisi lainnya (' + others.length + ')</button>';
        }

        container.innerHTML = html;
        attachDeliveryListeners(null, null, null);
    });
}

function toggleOtherEkspedisi(btn) {
    const wrap = document.getElementById('otherEkspedisiWrap');
    const shown = wrap.style.display !== 'none';
    wrap.style.display = shown ? 'none' : 'block';
    btn.innerHTML = shown
        ? '<i class="fas fa-chevron-down"></i> Lihat ekspedisi lainnya'
        : '<i class="fas fa-chevron-up"></i> Sembunyikan';
    if (!shown) attachDeliveryListeners(null, null, null);
}

function attachDeliveryListeners(distance, cityId, outlet) {
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const cost = parseInt(this.dataset.cost) || 0;
            updateShippingCost(cost);
            document.getElementById('scheduleCard').style.display = 'block';
            updateTimeSlots(this.value);
            updateCODAvailability(this.value);
            
            // Store courier info for form submission
            if (this.dataset.courier) {
                document.getElementById('selectedCourier').value = this.dataset.courier;
                document.getElementById('selectedService').value = this.dataset.service;
            } else {
                document.getElementById('selectedCourier').value = '';
                document.getElementById('selectedService').value = '';
            }
        });
    });
}

function updateShippingCost(cost) {
    document.getElementById('shippingCost').textContent = formatRupiah(cost);
    document.getElementById('totalAmount').textContent = formatRupiah(subtotal + cost);
    document.getElementById('shippingCostValue').value = cost;
}

function updateTimeSlots(method) {
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const deliveryDateContainer = document.getElementById('deliveryDateContainer');
    const ekspedisiInfo = document.getElementById('ekspedisiScheduleInfo');
    const deliveryDate = document.getElementById('deliveryDate');
    const deliveryTime = document.getElementById('deliveryTime');
    const scheduleTitle = document.getElementById('scheduleTitle');
    const deliveryDateLabel = document.getElementById('deliveryDateLabel');
    const timeSlotLabel = document.getElementById('timeSlotLabel');

    if (method === 'ekspedisi') {
        // Sembunyikan date picker, tampilkan info PO
        deliveryDateContainer.style.display = 'none';
        ekspedisiInfo.style.display = 'block';
        timeSlotContainer.style.display = 'none';
        deliveryDate.required = false;
        deliveryTime.required = false;
        scheduleTitle.textContent = 'Jadwal Pengiriman';

    } else if (method === 'pick_up') {
        // Tampilkan date picker dengan label "Tanggal Pick Up"
        deliveryDateContainer.style.display = 'block';
        ekspedisiInfo.style.display = 'none';
        deliveryDate.required = true;
        scheduleTitle.textContent = 'Jadwal Pick Up';
        deliveryDateLabel.textContent = 'Pilih Tanggal Pick Up';
        timeSlotLabel.textContent = 'Jam Pick Up';

        timeSlotContainer.style.display = 'block';
        deliveryTime.required = true;
        loadTimeSlots(deliveryTime);

    } else if (method === 'kurir_toko') {
        // Tampilkan date picker normal
        deliveryDateContainer.style.display = 'block';
        ekspedisiInfo.style.display = 'none';
        deliveryDate.required = true;
        scheduleTitle.textContent = 'Jadwal Pengiriman';
        deliveryDateLabel.textContent = 'Pilih Tanggal Pengiriman';
        timeSlotLabel.textContent = 'Pilih Waktu Pengiriman';

        timeSlotContainer.style.display = 'block';
        deliveryTime.required = true;
        loadTimeSlots(deliveryTime);

    } else {
        // Reset
        deliveryDateContainer.style.display = 'block';
        ekspedisiInfo.style.display = 'none';
        timeSlotContainer.style.display = 'none';
        deliveryDate.required = false;
        deliveryTime.required = false;
    }
}

function loadTimeSlots(select) {
    fetch(siteUrl + '/api/get_timeslots.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.timeslots.length > 0) {
                let options = '<option value="">Pilih waktu</option>';
                data.timeslots.forEach(slot => {
                    options += `<option value="${slot.time_slot}">${slot.time_slot}</option>`;
                });
                select.innerHTML = options;
            } else {
                select.innerHTML = `
                    <option value="">Pilih waktu</option>
                    <option value="09.00 - 12.00 WIB">09.00 - 12.00 WIB</option>
                    <option value="12.00 - 15.00 WIB">12.00 - 15.00 WIB</option>
                    <option value="15.00 - 18.00 WIB">15.00 - 18.00 WIB</option>
                `;
            }
        })
        .catch(() => {
            select.innerHTML = `
                <option value="">Pilih waktu</option>
                <option value="09.00 - 12.00 WIB">09.00 - 12.00 WIB</option>
                <option value="12.00 - 15.00 WIB">12.00 - 15.00 WIB</option>
                <option value="15.00 - 18.00 WIB">15.00 - 18.00 WIB</option>
            `;
        });
}

function updateCODAvailability(method) {
    const codOption = document.getElementById('codOption');
    const codRadio  = document.getElementById('payCod');
    if (method === 'ekspedisi') {
        codOption.style.display = 'none';
        codRadio.checked  = false;
        codRadio.disabled = true;
    } else {
        codOption.style.display = 'block';
        codRadio.disabled = false;
    }
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}
</script>

<?php include 'includes/footer.php'; ?>
