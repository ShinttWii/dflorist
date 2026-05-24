<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderNumber = $_POST['order_number'] ?? '';
if (!$orderNumber) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_number = ? AND o.user_id = ?");
$stmt->execute([$orderNumber, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if ($order['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Already paid']);
    exit;
}

// Load Midtrans config
$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? getenv('MIDTRANS_SERVER_KEY') ?? '';
$isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? getenv('MIDTRANS_IS_PRODUCTION') ?? 'false') === 'true';
$baseUrl = $isProduction ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

$itemDetails = [];
foreach ($items as $item) {
    $itemDetails[] = [
        'id'       => 'PROD-' . $item['product_id'],
        'price'    => (int) $item['price'],
        'quantity' => (int) $item['quantity'],
        'name'     => substr($item['product_name'], 0, 50),
    ];
}

// Tambah ongkir sebagai item jika ada
if ($order['shipping_cost'] > 0) {
    $itemDetails[] = [
        'id'       => 'SHIPPING',
        'price'    => (int) $order['shipping_cost'],
        'quantity' => 1,
        'name'     => 'Ongkos Kirim',
    ];
}

$params = [
    'transaction_details' => [
        'order_id'     => $order['order_number'],
        'gross_amount' => (int) $order['total'],
    ],
    'item_details'    => $itemDetails,
    'customer_details' => [
        'first_name' => $order['customer_name'],
        'email'      => $order['customer_email'],
        'phone'      => $order['customer_phone'] ?? '',
    ],
    'expiry' => [
        'unit'     => 'minutes',
        'duration' => 25,
    ],
    'callbacks' => [
        'finish' => SITE_URL . '/order_success.php?order=' . $order['order_number'],
    ],
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($serverKey . ':'),
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 201 && isset($result['token'])) {
    echo json_encode(['success' => true, 'token' => $result['token'], 'redirect_url' => $result['redirect_url']]);
} else {
    echo json_encode(['success' => false, 'message' => $result['error_messages'][0] ?? 'Gagal membuat token pembayaran', 'debug' => $result]);
}
?>
