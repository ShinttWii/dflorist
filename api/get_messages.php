<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conversationId = $_GET['conversation_id'] ?? 0;
$lastId = $_GET['last_id'] ?? 0;

try {
    // Verify conversation belongs to user
    $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$conversationId, $_SESSION['customer_id']]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }
    
    // Get new messages
    $stmt = $pdo->prepare("
        SELECT cm.*, a.name as admin_name
        FROM chat_messages cm
        LEFT JOIN admins a ON cm.sender_id = a.id AND cm.sender_type = 'admin'
        WHERE cm.conversation_id = ? AND cm.id > ? AND cm.sender_type != 'customer'
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$conversationId, $lastId]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND id > ? AND sender_type != 'customer'");
        $stmt->execute([$conversationId, $lastId]);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
