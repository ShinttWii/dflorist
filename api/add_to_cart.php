<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login terlebih dahulu', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

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

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$currentQty = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
$newQty = $currentQty + $quantity;

if ($newQty > $stock) {
    $newQty = $stock;
}

if ($newQty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
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

$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode(['success' => true, 'cart_count' => $cartCount, 'message' => 'Produk ditambahkan ke keranjang']);
