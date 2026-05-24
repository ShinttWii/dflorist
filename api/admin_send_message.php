<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conversationId = $data['conversation_id'] ?? 0;
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

try {
    // Verify conversation exists
    $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message)
        VALUES (?, 'admin', ?, ?)
    ");
    $stmt->execute([$conversationId, $_SESSION['admin_id'], $message]);
    
    // Update conversation last_message_at
    $stmt = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent',
        'message_id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
