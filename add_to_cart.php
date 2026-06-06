<?php
// Memulai session dan memanggil fungsi inti aplikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

// Atur response agar mengeluarkan format JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data produk atau kuantiti tidak valid.']);
    exit;
}

try {
    // 1. Validasi produk apakah ada di database dan cek stoknya
    $stmt = $pdo->prepare("SELECT id, name, price, is_promo, promo_price, stock, image FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan atau sudah tidak aktif.']);
        exit;
    }

    if ($product['stock'] < $quantity) {
        echo json_encode(['status' => 'error', 'message' => 'Stok tidak mencukupi. Sisa stok: ' . $product['stock']]);
        exit;
    }

    // Tentukan harga yang dipakai (apakah harga promo atau normal)
    $finalPrice = ($product['is_promo'] == 1 && $product['promo_price'] > 0) ? $product['promo_price'] : $product['price'];

    // 2. KONDISI: Jika Customer SUDAH LOGIN (Simpan ke Database & Session)
    if (isCustomerLoggedIn()) {
        $customerId = $_SESSION['customer_id'];

        // Cek apakah item ini sudah pernah masuk ke keranjang database sebelumnya
        $checkStmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$customerId, $productId]);
        $existingCart = $checkStmt->fetch();

        if ($existingCart) {
            // Jika sudah ada, tambahkan quantity lama dengan yang baru
            $newQuantity = $existingCart['quantity'] + $quantity;
            
            // Re-check stok total belanjaan vs stok gudang
            if ($product['stock'] < $newQuantity) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambah barang. Total item di keranjang Anda melebihi stok tersedia.']);
                exit;
            }

            $updateStmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $updateStmt->execute([$newQuantity, $existingCart['id']]);
        } else {
            // Jika belum ada, lakukan INSERT data baru
            $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insertStmt->execute([$customerId, $productId, $quantity]);
        }

        // Singkronisasikan ulang isi database ke $_SESSION['cart'] agar data tetap sinkron
        loadCartFromDb($pdo, $customerId);

    } else {
        // 3. KONDISI: Jika Guest / BELUM LOGIN (Simpan di Session Saja)
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $newQuantity = $_SESSION['cart'][$productId]['quantity'] + $quantity;
            if ($product['stock'] < $newQuantity) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambah barang. Total item di keranjang Anda melebihi stok tersedia.']);
                exit;
            }
            $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id'     => $productId,
                'product_name'   => $product['name'],
                'price'          => (float)$finalPrice,
                'original_price' => (float)$product['price'],
                'is_promo'       => $product['is_promo'],
                'quantity'       => $quantity,
                'image'          => $product['image'],
                'stock'          => (int)$product['stock'],
                'selected'       => true,
            ];
        }
    }

    // Kembalikan respon sukses beserta jumlah item terbaru di keranjang
    echo json_encode([
        'status' => 'success',
        'cart_count' => getCartCount()
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    exit;
}