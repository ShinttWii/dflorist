<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$productId = intval($_POST['product_id'] ?? 0);
$quantity  = intval($_POST['quantity'] ?? 1);

if (!$productId || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit;
}

$stock = $product['stock'];
$price = $product['is_promo'] ? $product['promo_price'] : $product['price'];

if (isCustomerLoggedIn()) {
    // User login: simpan ke database + sinkron ke session
    $customerId = $_SESSION['customer_id'];

    $checkStmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$customerId, $productId]);
    $existingCart = $checkStmt->fetch();

    if ($existingCart) {
        $newQty = $existingCart['quantity'] + $quantity;
        if ($newQty > $stock) {
            echo json_encode(['success' => false, 'message' => 'Total item melebihi stok tersedia.']);
            exit;
        }
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $existingCart['id']]);
    } else {
        if ($quantity > $stock) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi.']);
            exit;
        }
        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)")->execute([$customerId, $productId, $quantity]);
    }

    loadCartFromDb($pdo, $customerId);
} else {
    // Guest: simpan ke session saja
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $currentQty = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
    $newQty = $currentQty + $quantity;

    if ($newQty > $stock) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi.']);
        exit;
    }

    $_SESSION['cart'][$productId] = [
        'product_id'     => $productId,
        'product_name'   => $product['name'],
        'price'          => $price,
        'original_price' => $product['price'],
        'is_promo'       => $product['is_promo'],
        'quantity'       => $newQty,
        'image'          => $product['image'],
        'stock'          => $stock,
        'selected'       => true,
    ];
}

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

echo json_encode(['success' => true, 'cart_count' => $cartCount, 'message' => 'Produk ditambahkan ke keranjang']);
