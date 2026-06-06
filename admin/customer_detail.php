<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

$customerId = $_GET['id'] ?? 0;

// Get customer data
$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE id = ? AND role = 'customer'
");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Status akun tidak bisa dideteksi secara real-time tanpa kolom last_seen
$isOnline = false;

// Get customer addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_primary DESC");
$stmt->execute([$customerId]);
$addresses = $stmt->fetchAll();

// Get order history
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll();

// Calculate statistics (exclude cancelled orders)
$totalOrders = count($orders);
$completedOrders = 0;
$totalProductPrice = 0; // Total harga produk (subtotal)
$totalShippingCost = 0; // Total ongkir
$totalSpent = 0; // Total keseluruhan

foreach ($orders as $order) {
    // Skip cancelled orders for spending calculation
    if ($order['order_status'] !== 'dibatalkan') {
        $totalProductPrice += $order['subtotal'];
        $totalShippingCost += $order['shipping_cost'];
        $totalSpent += $order['total'];
    }
    
    if ($order['order_status'] === 'selesai') {
        $completedOrders++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pelanggan - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-weight: 600;
            color: #2C3E50;
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .stat-box h3 {
            color: #FF69B4;
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }
        .stat-box p {
            color: #6c757d;
            margin: 0;
            font-size: 0.85rem;
        }
        .stat-box small {
            font-size: 0.75rem;
        }
        .stat-divider {
            margin: 0.5rem 0;
            border-top: 1px solid #e9ecef;
        }
        .address-box {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .address-box.primary {
            border-color: #FF69B4;
            background-color: rgba(255, 214, 232, 0.2);
        }
        .card + .card {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detail Pelanggan</h1>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                
                <div class="row">
                    <!-- Customer Info -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user"></i> Informasi Pelanggan</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-card">
                                    <div class="info-label">Nama Lengkap</div>
                                    <div class="info-value"><?php echo htmlspecialchars($customer['name']); ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">No. HP</div>
                                    <div class="info-value"><?php echo htmlspecialchars($customer['phone']); ?></div>
                                </div>
                                <div class="info-card">
                                    <div class="info-label">Terdaftar Sejak</div>
                                    <div class="info-value"><?php echo date('d F Y', strtotime($customer['created_at'])); ?></div>
                                </div>

                            </div>
                        </div>
                        
                        <!-- Statistics -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistik</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="stat-box">
                                            <h3><?php echo $totalOrders; ?></h3>
                                            <p>Total Pesanan</p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-box">
                                            <h3><?php echo $completedOrders; ?></h3>
                                            <p>Pesanan Selesai</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-divider"></div>
                                
                                <div class="stat-box">
                                    <h3 class="text-primary"><?php echo formatRupiah($totalProductPrice); ?></h3>
                                    <p>Total Belanja Produk</p>
                                </div>
                                

                            </div>
                        </div>
                    </div>
                    
                    <!-- Addresses & Orders -->
                    <div class="col-md-8">
                        <!-- Addresses -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($addresses)): ?>
                                <p class="text-muted">Belum ada alamat tersimpan</p>
                                <?php else: ?>
                                    <?php 
                                    $primaryAddress = $addresses[0]; // Alamat pertama (utama)
                                    $otherAddresses = array_slice($addresses, 1); // Alamat lainnya
                                    ?>
                                    
                                    <!-- Alamat Utama -->
                                    <div class="address-box <?php echo $primaryAddress['is_primary'] ? 'primary' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong style="color: #2C3E50;"><?php echo htmlspecialchars($primaryAddress['label']); ?></strong>
                                                <?php if ($primaryAddress['is_primary']): ?>
                                                <span class="badge ms-2" style="background-color: #FF69B4; color: white;">Utama</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="mb-1 mt-2" style="color: #2C3E50;"><?php echo htmlspecialchars($primaryAddress['recipient_name']); ?> - <?php echo htmlspecialchars($primaryAddress['recipient_phone']); ?></p>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($primaryAddress['address']); ?></p>
                                        <?php if ($primaryAddress['notes']): ?>
                                        <p class="mb-0 text-muted small"><em>Catatan: <?php echo htmlspecialchars($primaryAddress['notes']); ?></em></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Alamat Lainnya (Hidden) -->
                                    <?php if (!empty($otherAddresses)): ?>
                                    <div id="otherAddresses" style="display: none;">
                                        <?php foreach ($otherAddresses as $addr): ?>
                                        <div class="address-box">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong style="color: #2C3E50;"><?php echo htmlspecialchars($addr['label']); ?></strong>
                                                </div>
                                            </div>
                                            <p class="mb-1 mt-2" style="color: #2C3E50;"><?php echo htmlspecialchars($addr['recipient_name']); ?> - <?php echo htmlspecialchars($addr['recipient_phone']); ?></p>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($addr['address']); ?></p>
                                            <?php if ($addr['notes']): ?>
                                            <p class="mb-0 text-muted small"><em>Catatan: <?php echo htmlspecialchars($addr['notes']); ?></em></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="toggleAddresses()">
                                        <i class="fas fa-eye" id="toggleIcon"></i> 
                                        <span id="toggleText">Lihat Semua Alamat (<?php echo count($otherAddresses); ?>)</span>
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Order History -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Riwayat Pesanan</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                <p class="text-muted">Belum ada riwayat pesanan</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No. Pesanan</th>
                                                <th>Tanggal</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($orders, 0, 4) as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo formatRupiah($order['total']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (count($orders) > 4): ?>
                                <div id="moreOrders" style="display:none;">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <tbody>
                                            <?php foreach (array_slice($orders, 4) as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo formatRupiah($order['total']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary w-100 mt-2" onclick="toggleOrders(this)">
                                    <i class="fas fa-list me-1"></i> Lihat Semua Pesanan (<?php echo count($orders); ?>)
                                </button>
                                <?php endif; ?>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleOrders(btn) {
        const more = document.getElementById('moreOrders');
        if (more.style.display === 'none') {
            more.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Sembunyikan';
        } else {
            more.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-list me-1"></i> Lihat Semua Pesanan (<?php echo count($orders); ?>)';
        }
    }

    function toggleAddresses() {
        const otherAddresses = document.getElementById('otherAddresses');
        const toggleIcon = document.getElementById('toggleIcon');
        const toggleText = document.getElementById('toggleText');
        
        if (otherAddresses.style.display === 'none') {
            otherAddresses.style.display = 'block';
            toggleIcon.className = 'fas fa-eye-slash';
            toggleText.textContent = 'Sembunyikan Alamat Lainnya';
        } else {
            otherAddresses.style.display = 'none';
            toggleIcon.className = 'fas fa-eye';
            toggleText.textContent = 'Lihat Semua Alamat (<?php echo count($otherAddresses ?? []); ?>)';
        }
    }
    </script>
</body>
</html>
