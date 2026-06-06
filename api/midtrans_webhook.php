<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Midtrans akan POST ke endpoint ini
$payload = file_get_contents('php://input');
$data    = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid payload');
}

$serverKey   = getSetting($pdo, 'midtrans_server_key') ?: ($_ENV['MIDTRANS_SERVER_KEY'] ?? getenv('MIDTRANS_SERVER_KEY') ?? '');
$orderId     = $data['order_id']          ?? '';
$statusCode  = $data['status_code']       ?? '';
$grossAmount = $data['gross_amount']      ?? '';
$signatureKey = $data['signature_key']   ?? '';

// Verifikasi signature
$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
if ($signatureKey !== $expectedSignature) {
    http_response_code(403);
    exit('Invalid signature');
}

$transactionStatus = $data['transaction_status'] ?? '';
$fraudStatus       = $data['fraud_status']       ?? '';
$paymentType       = $data['payment_type']       ?? '';

// Tentukan status berdasarkan response Midtrans
$newPaymentStatus = null;
$newOrderStatus   = null;

if ($transactionStatus === 'capture') {
    if ($fraudStatus === 'accept') {
        $newPaymentStatus = 'paid';
        $newOrderStatus   = 'dibayar';
    }
} elseif ($transactionStatus === 'settlement') {
    $newPaymentStatus = 'paid';
    $newOrderStatus   = 'dibayar';
} elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
    $newPaymentStatus = 'failed';
    $newOrderStatus   = 'menunggu_pembayaran';
} elseif ($transactionStatus === 'pending') {
    $newPaymentStatus = 'pending';
}

if ($newPaymentStatus) {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, order_status = COALESCE(?, order_status), payment_method = ? WHERE order_number = ?");
    $stmt->execute([$newPaymentStatus, $newOrderStatus, $paymentType, $orderId]);
}

http_response_code(200);
echo 'OK';
?>
