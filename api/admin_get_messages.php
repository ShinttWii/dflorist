<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conversationId = $_GET['conversation_id'] ?? 0;
$lastId = $_GET['last_id'] ?? 0;

try {
    // Get new messages
    $stmt = $pdo->prepare("
        SELECT cm.*, u.name as customer_name
        FROM chat_messages cm
        LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'customer'
        WHERE cm.conversation_id = ? AND cm.id > ? AND cm.sender_type = 'customer'
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$conversationId, $lastId]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND id > ? AND sender_type = 'customer'");
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
