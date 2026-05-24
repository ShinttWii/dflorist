<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderNumber = $data['order_number'] ?? '';
$paymentMethod = $data['payment_method'] ?? '';

if (empty($orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit;
}

try {
    // Update payment status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'paid', order_status = 'dibayar', updated_at = NOW()
        WHERE order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    
    // Send email notification (implement with PHPMailer)
    // sendEmail($customerEmail, "Pembayaran Berhasil", "Pembayaran untuk pesanan $orderNumber berhasil");
    
    echo json_encode([
        'success' => true,
        'message' => 'Pembayaran berhasil diproses',
        'order_number' => $orderNumber
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
    ]);
}
