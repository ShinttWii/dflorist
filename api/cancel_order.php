<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$orderId = $_POST['order_id'] ?? null;
$userId = $_SESSION['customer_id']; // Fix: use customer_id instead of user_id

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
        exit;
    }
    
    // Check if already cancelled
    if ($order['order_status'] === 'dibatalkan') {
        echo json_encode(['success' => false, 'message' => 'Pesanan sudah dibatalkan']);
        exit;
    }
    
    // Check if order can be cancelled
    if (in_array($order['order_status'], ['dikirim', 'selesai'])) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan']);
        exit;
    }
    
    // Check if there's already a pending cancellation request
    $stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Permintaan pembatalan sudah diajukan sebelumnya']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // If pending payment, cancel directly
    if ($order['order_status'] === 'menunggu_pembayaran') {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'dibatalkan', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Restore stock
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan', 'type' => 'direct']);
        exit;
    }
    
    // For COD orders with status 'diproses', create cancellation request
    if ($order['payment_method'] === 'cod' && $order['order_status'] === 'diproses') {
        $stmt = $pdo->prepare("INSERT INTO cancellation_requests (order_id, user_id) VALUES (?, ?)");
        $stmt->execute([$orderId, $userId]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Permintaan pembatalan telah diajukan, menunggu persetujuan admin', 'type' => 'request']);
        exit;
    }
    
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan']);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
