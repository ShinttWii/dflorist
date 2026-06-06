<?php
/**
 * D'florist E-Commerce System
 * * Sistem e-commerce untuk toko bunga dengan konsep pre-order dan pengiriman terjadwal.
 * Mengadopsi best practices dari Alfagift (alfagift.id) dengan penyesuaian untuk toko bunga.
 * * Fitur utama yang diadopsi dari Alfagift:
 * - Multi-address management dengan Google Maps
 * - Slot waktu pengiriman terjadwal
 * - Multiple metode pengiriman
 * - Checkout flow yang smooth
 * - Order tracking dengan timeline
 * - Review & rating system
 * * Fitur khusus D'florist:
 * - Pre-order minimal H+2
 * - Perhitungan jarak otomatis untuk metode pengiriman
 * - Sistem kuota pengiriman (max 5 per hari per metode)
 * - OTP untuk lupa password
 * * @version 1.0.0
 * @author D'florist Development Team
 * @reference Alfagift (alfagift.id)
 */

require_once __DIR__ . '/../config/database.php';

// Load environment variables from .env file
if (!function_exists('loadEnv')) {
    function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
}
loadEnv();

// Fungsi untuk hash password
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

// Fungsi untuk verifikasi password
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Fungsi untuk generate order number
if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber() {
        return 'DF' . date('Ymd') . rand(1000, 9999);
    }
}

// Fungsi untuk menghitung jarak (Haversine formula)
if (!function_exists('calculateDistance')) {
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
}

// Fungsi untuk mencari outlet terdekat
if (!function_exists('findNearestOutlet')) {
    function findNearestOutlet($pdo, $customerLat, $customerLng) {
        $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1");
        $outlets = $stmt->fetchAll();
        
        if (empty($outlets)) {
            return null;
        }

        // Kalau koordinat tidak valid (0,0 atau kosong), return outlet pertama
        if (!$customerLat || !$customerLng || ($customerLat == 0 && $customerLng == 0)) {
            $outlets[0]['distance'] = 0;
            return $outlets[0];
        }
        
        $nearestOutlet = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($outlets as $outlet) {
            $distance = calculateDistance(
                $customerLat, $customerLng,
                $outlet['latitude'], $outlet['longitude']
            );
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestOutlet = $outlet;
                $nearestOutlet['distance'] = $distance;
            }
        }
        
        return $nearestOutlet;
    }
}

// Fungsi untuk cek kuota pengiriman
if (!function_exists('checkDeliveryQuota')) {
    function checkDeliveryQuota($pdo, $date, $method) {
        $stmt = $pdo->prepare("
            SELECT current_quota, max_quota 
            FROM delivery_quotas 
            WHERE delivery_date = ? AND delivery_method = ?
        ");
        $stmt->execute([$date, $method]);
        $quota = $stmt->fetch();
        
        if (!$quota) {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'max_quota_per_date'");
            $stmt->execute();
            $maxQuota = $stmt->fetchColumn() ?: 5;
            return ['current' => 0, 'max' => $maxQuota, 'available' => true];
        }
        
        return [
            'current' => $quota['current_quota'],
            'max' => $quota['max_quota'],
            'available' => $quota['current_quota'] < $quota['max_quota']
        ];
    }
}

// Fungsi untuk update kuota
if (!function_exists('updateDeliveryQuota')) {
    function updateDeliveryQuota($pdo, $date, $method) {
        $stmt = $pdo->prepare("
            INSERT INTO delivery_quotas (delivery_date, delivery_method, current_quota, max_quota)
            VALUES (?, ?, 1, (SELECT setting_value FROM settings WHERE setting_key = 'max_quota_per_date'))
            ON DUPLICATE KEY UPDATE current_quota = current_quota + 1
        ");
        return $stmt->execute([$date, $method]);
    }
}

// Fungsi untuk mendapatkan pengaturan
if (!function_exists('getSetting')) {
    function getSetting($pdo, $key) {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }
}

// Fungsi untuk format rupiah
if (!function_exists('formatRupiah')) {
    function formatRupiah($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

// Fungsi untuk sanitize input
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}

// Fungsi untuk cek login customer
if (!function_exists('isCustomerLoggedIn')) {
    function isCustomerLoggedIn() {
        return isset($_SESSION['customer_id']);
    }
}

// Fungsi untuk cek login admin
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}

// Fungsi untuk redirect
if (!function_exists('redirect')) {
    function redirect($url) {
        // Clear any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        header("Location: $url");
        exit;
    }
}

// Fungsi untuk generate reset token
if (!function_exists('generateResetToken')) {
    function generateResetToken() {
        return bin2hex(random_bytes(32));
    }
}

// ==========================================
// FUNGSI BARU: Load Cart dari Database
// ==========================================
if (!function_exists('loadCartFromDb')) {
    function loadCartFromDb($pdo, $customerId) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.product_id, c.quantity,
                       p.name, p.price, p.promo_price, p.is_promo, p.image, p.stock
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$customerId]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $_SESSION['cart'] = [];

            foreach ($cartItems as $item) {
                $finalPrice = ($item['is_promo'] && $item['promo_price'] > 0)
                    ? (float)$item['promo_price']
                    : (float)$item['price'];

                $_SESSION['cart'][$item['product_id']] = [
                    'product_id'     => $item['product_id'],
                    'product_name'   => $item['name'],
                    'price'          => $finalPrice,
                    'original_price' => (float)$item['price'],
                    'is_promo'       => $item['is_promo'],
                    'quantity'       => (int)$item['quantity'],
                    'image'          => $item['image'],
                    'stock'          => (int)$item['stock'],
                    'selected'       => true,
                ];
            }
        } catch (PDOException $e) {
            error_log("D'Florist Cart Load Error: " . $e->getMessage());
        }
    }
}

// Fungsi untuk get cart total
if (!function_exists('getCartTotal')) {
    function getCartTotal() {
        $total = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }
}

// Fungsi untuk get cart count
if (!function_exists('getCartCount')) {
    function getCartCount() {
        if (!isset($_SESSION['cart'])) {
            return 0;
        }
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
}

// Fungsi untuk get total weight dari cart
if (!function_exists('getCartTotalWeight')) {
    function getCartTotalWeight($pdo) {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return 0;
        }
        
        $totalWeight = 0;
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("SELECT weight FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            
            if ($product) {
                $totalWeight += ($product['weight'] * $item['quantity']);
            }
        }
        
        return $totalWeight;
    }
}

// Fungsi untuk hitung ongkir ekspedisi
if (!function_exists('calculateShippingCost')) {
    function calculateShippingCost($pdo, $method, $distance, $totalWeight) {
        if ($method === 'pick_up') {
            return 0;
        }
        
        if ($method === 'kurir_toko') {
            $cost = getSetting($pdo, 'kurir_toko_cost') ?: 10000;
            return (int)$cost;
        }
        
        if ($method === 'ekspedisi') {
            $costPerKg = getSetting($pdo, 'ekspedisi_cost_per_kg') ?: 10000;
            
            // Pembulatan berat ke atas
            $weightRounded = ceil($totalWeight);
            
            // Base cost berdasarkan berat
            $baseCost = $weightRounded * $costPerKg;
            
            // Tambahan biaya berdasarkan tier jarak (editable di admin)
            $tier1 = getSetting($pdo, 'ekspedisi_tier_1') ?: 5000;   // 201-400 km
            $tier2 = getSetting($pdo, 'ekspedisi_tier_2') ?: 10000;  // 401-600 km
            $tier3 = getSetting($pdo, 'ekspedisi_tier_3') ?: 15000;  // >600 km
            
            $extraCost = 0;
            if ($distance > 600) {
                $extraCost = $tier3;
            } elseif ($distance > 400) {
                $extraCost = $tier2;
            } elseif ($distance > 200) {
                $extraCost = $tier1;
            }
            
            return (int)($baseCost + $extraCost);
        }
        
        return 0;
    }
}

// ==========================================
// FUNGSI: Format label metode pembayaran
// ==========================================
if (!function_exists('formatPaymentMethod')) {
    function formatPaymentMethod($method) {
        $labels = [
            // Nilai saat order dibuat
            'midtrans'      => 'Bayar Online (Midtrans)',
            'cod'           => 'COD (Bayar di Tempat)',

            // Nilai yang diisi Midtrans webhook setelah pembayaran berhasil
            'credit_card'   => 'Kartu Kredit / Debit',
            'qris'          => 'QRIS',
            'gopay'         => 'GoPay',
            'shopeepay'     => 'ShopeePay',
            'bank_transfer' => 'Transfer Bank (Virtual Account)',
            'bca_va'        => 'Transfer Bank BCA (Virtual Account)',
            'bni_va'        => 'Transfer Bank BNI (Virtual Account)',
            'bri_va'        => 'Transfer Bank BRI (Virtual Account)',
            'permata_va'    => 'Transfer Bank Permata (Virtual Account)',
            'other_va'      => 'Transfer Bank (Virtual Account)',
            'echannel'      => 'Mandiri Bill Payment',
            'cstore'        => 'Minimarket (Indomaret / Alfamart)',
            'indomaret'     => 'Indomaret',
            'alfamart'      => 'Alfamart',
            'akulaku'       => 'Akulaku (Cicilan)',
            'kredivo'       => 'Kredivo (Cicilan)',
        ];

        if (isset($labels[$method])) {
            return $labels[$method];
        }

        // Fallback: ubah underscore jadi spasi, kapitalisasi tiap kata
        return ucwords(str_replace('_', ' ', $method));
    }
}

// Auto-cancel pesanan midtrans yang belum dibayar lebih dari 24 jam
if (!function_exists('cancelExpiredOrders')) {
    function cancelExpiredOrders($pdo, $userId = null) {
        $sql = "UPDATE orders 
                SET payment_status = 'failed', order_status = 'dibatalkan'
                WHERE payment_status = 'pending'
                  AND payment_method = 'midtrans'
                  AND order_status = 'menunggu_pembayaran'
                  AND created_at < DATE_SUB(NOW(), INTERVAL 25 MINUTE)";
        $params = [];
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        $pdo->prepare($sql)->execute($params);
    }
}