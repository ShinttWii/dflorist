<?php
$pageTitle = 'Chat Customer Service - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php?redirect=chat.php');
}

$userId = $_SESSION['customer_id'];

// Get or create conversation
$stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$conversation = $stmt->fetch();

if (!$conversation) {
    // Create new conversation
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    $conversationId = $pdo->lastInsertId();
    
    // Send auto-reply greeting (3 pesan terpisah)
    $messages_bot = [
        "Halo! Selamat datang di D'florist Customer Service. 🌸",
        "Ada yang bisa kami bantu?",
        "Kamu bisa tanyakan seputar:\n• Produk & harga\n• Status pesanan\n• Cara pemesanan\n• Metode pembayaran\n\nAdmin kami akan segera membalas. 😊"
    ];
    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (?, 'system', ?)");
    foreach ($messages_bot as $m) {
        $stmt->execute([$conversationId, $m]);
    }
} else {
    $conversationId = $conversation['id'];
}

// Check admin online status (online if last seen within 5 minutes)
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_last_seen'");
$stmt->execute();
$lastSeen = $stmt->fetchColumn();
$isAdminOnline = $lastSeen && (time() - strtotime($lastSeen)) < 300;

// Get messages
$stmt = $pdo->prepare("
    SELECT cm.*, u.name as customer_name, a.name as admin_name
    FROM chat_messages cm
    LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'customer'
    LEFT JOIN admins a ON cm.sender_id = a.id AND cm.sender_type = 'admin'
    WHERE cm.conversation_id = ?
    ORDER BY cm.created_at ASC
");
$stmt->execute([$conversationId]);
$messages = $stmt->fetchAll();

// Mark messages as read
$stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type != 'customer'");
$stmt->execute([$conversationId]);
?>

<style>
.chat-container {
    max-width: 800px;
    margin: 0 auto;
    height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: var(--pink-pastel);
    padding: 20px;
    border-radius: 15px 15px 0 0;
    color: var(--text-dark);
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    border-left: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
}

.message {
    margin-bottom: 6px;
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.message.customer {
    justify-content: flex-end;
}

.message.admin, .message.system {
    justify-content: flex-start;
}

.message-content {
    max-width: 70%;
    display: flex;
    flex-direction: column;
}

.message.customer .message-content {
    align-items: flex-end;
}

.message.admin .message-content,
.message.system .message-content {
    align-items: flex-start;
}

.message-bubble {
    padding: 8px 14px;
    border-radius: 18px;
    word-wrap: break-word;
    white-space: pre-wrap;
    display: inline-block;
    line-height: 1.4;
}

.message.customer .message-bubble {
    background: var(--pink-pastel);
    color: var(--text-dark);
    border-bottom-right-radius: 4px;
}

.message.admin .message-bubble {
    background: white;
    border: 1px solid #dee2e6;
    color: var(--text-dark);
    border-bottom-left-radius: 4px;
}

.message.system .message-bubble {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    text-align: left;
    border-radius: 18px;
    font-size: 0.85rem;
    max-width: 320px;
    padding: 8px 14px;
    line-height: 1.4;
}

.message-info {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 4px;
    padding: 0 4px;
}

.chat-input-container {
    background: white;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 0 0 15px 15px;
}

.chat-input {
    display: flex;
    gap: 10px;
}

.chat-input textarea {
    flex: 1;
    border-radius: 25px;
    padding: 12px 20px;
    border: 1px solid #dee2e6;
    resize: none;
    font-size: 0.95rem;
}

.chat-input textarea:focus {
    outline: none;
    border-color: var(--pink-dark);
    box-shadow: 0 0 0 0.2rem rgba(255, 158, 199, 0.25);
}

.btn-send {
    background: var(--pink-dark);
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-send:hover {
    background: #FF69B4;
    transform: scale(1.1);
}

.btn-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.typing-indicator {
    display: none;
    padding: 10px;
    font-style: italic;
    color: #6c757d;
}

.typing-indicator.show {
    display: block;
}

.online-status {
    display: inline-block;
    width: 10px;
    height: 10px;
    background: #28a745;
    border-radius: 50%;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@media (max-width: 768px) {
    .chat-container {
        height: calc(100vh - 150px);
    }
    
    .message-bubble {
        max-width: 85%;
    }
}
</style>

<div class="container my-4">
    <div class="chat-container card">
        <div class="chat-header">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-headset fa-2x"></i>
                </div>
                <div>
                    <h5 class="mb-0">Customer Service D'florist</h5>
                    <small>
                        <?php if ($isAdminOnline): ?>
                            <span class="online-status"></span> Online - Siap Membantu
                        <?php else: ?>
                            <span style="display:inline-block;width:10px;height:10px;background:#6c757d;border-radius:50%;margin-right:5px;"></span> Offline - Tinggalkan pesan, kami akan segera membalas
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo $msg['sender_type']; ?>">
                <div class="message-content">
                    <div class="message-bubble">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    <div class="message-info">
                        <?php if ($msg['sender_type'] === 'customer'): ?>
                            Anda
                        <?php elseif ($msg['sender_type'] === 'admin'): ?>
                            <?php echo htmlspecialchars($msg['admin_name'] ?? 'Admin'); ?>
                        <?php else: ?>
                            Sistem
                        <?php endif; ?>
                        • <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="typing-indicator" id="typingIndicator">
                Admin sedang mengetik...
            </div>
        </div>
        
        <div class="chat-input-container">
            <form id="chatForm" class="chat-input">
                <textarea 
                    id="messageInput" 
                    name="message" 
                    rows="1" 
                    placeholder="Ketik pesan Anda..."
                    required
                    onkeypress="handleKeyPress(event)"
                ></textarea>
                <button type="submit" class="btn-send" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            <small class="text-muted mt-2 d-block">
                <i class="fas fa-info-circle"></i> Tekan Enter untuk mengirim, Shift+Enter untuk baris baru
            </small>
        </div>
    </div>
</div>

<script>
const conversationId = <?php echo $conversationId; ?>;
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const typingIndicator = document.getElementById('typingIndicator');

// Auto-scroll to bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Initial scroll
scrollToBottom();

// Handle form submit
chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    // Disable input
    sendBtn.disabled = true;
    messageInput.disabled = true;
    
    // Send message
    fetch('api/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: conversationId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to chat
            addMessage('customer', message, 'Anda', new Date());
            messageInput.value = '';
            scrollToBottom();
        } else {
            alert('Gagal mengirim pesan: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    })
    .finally(() => {
        sendBtn.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
    });
});

// Handle Enter key
function handleKeyPress(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        chatForm.dispatchEvent(new Event('submit'));
    }
}

// Add message to chat
function addMessage(type, message, sender, time) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + type;
    
    const timeStr = time.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-bubble">${escapeHtml(message).replace(/\n/g, '<br>')}</div>
            <div class="message-info">${sender} • ${timeStr}</div>
        </div>
    `;
    
    chatMessages.insertBefore(messageDiv, typingIndicator);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Poll for new messages
let lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;

function pollMessages() {
    fetch('api/get_messages.php?conversation_id=' + conversationId + '&last_id=' + lastMessageId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const sender = msg.sender_type === 'admin' ? (msg.admin_name || 'Admin') : 'Sistem';
                    addMessage(msg.sender_type, msg.message, sender, new Date(msg.created_at));
                    lastMessageId = msg.id;
                });
                scrollToBottom();
                
                // Play notification sound (optional)
                // new Audio('notification.mp3').play();
            }
        })
        .catch(error => console.error('Error polling messages:', error));
}

// Poll every 3 seconds
setInterval(pollMessages, 3000);

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
</script>

<?php include 'includes/footer.php'; ?>
