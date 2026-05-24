<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$requestId = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null; // 'approve' or 'reject'
$rejectionReason = $_POST['rejection_reason'] ?? null;
$adminId = $_SESSION['admin_id'];

if (!$requestId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Request ID and action required']);
    exit;
}

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Get cancellation request
    $stmt = $pdo->prepare("SELECT cr.*, o.order_status FROM cancellation_requests cr 
                          JOIN orders o ON cr.order_id = o.id 
                          WHERE cr.id = ? AND cr.status = 'pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Permintaan pembatalan tidak ditemukan']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // Update order status to dibatalkan first
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'dibatalkan', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$request['order_id']]);
        
        // Restore stock
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$request['order_id']]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Check if admin_id exists in admins table
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $adminExists = $stmt->fetch();
        
        // Update cancellation request status
        if ($adminExists) {
            $stmt = $pdo->prepare("UPDATE cancellation_requests 
                                  SET status = 'approved', admin_id = ?, admin_response_date = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$adminId, $requestId]);
        } else {
            // If admin doesn't exist, set admin_id to NULL
            $stmt = $pdo->prepare("UPDATE cancellation_requests 
                                  SET status = 'approved', admin_id = NULL, admin_response_date = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$requestId]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pembatalan pesanan disetujui']);
        
    } else { // reject
        // Delete cancellation request (pesanan balik ke halaman orders biasa)
        $stmt = $pdo->prepare("DELETE FROM cancellation_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pembatalan pesanan ditolak, pesanan kembali ke daftar pesanan']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
