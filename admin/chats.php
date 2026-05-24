<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Update admin online status
$pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_last_seen', NOW()) ON DUPLICATE KEY UPDATE setting_value = NOW()")->execute();

$stmt = $pdo->query("
    SELECT cc.id, cc.user_id, cc.status, u.name AS customer_name, u.email AS customer_email,
           cc.last_message_at,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = cc.id AND cm.sender_type = 'customer' AND cm.is_read = 0) AS unread
    FROM chat_conversations cc
    JOIN users u ON cc.user_id = u.id
    WHERE cc.status = 'active'
    ORDER BY cc.last_message_at DESC
");
$conversations = $stmt->fetchAll();

$selectedConvId = isset($_GET['conv']) ? (int)$_GET['conv'] : 0;
$messages = [];
$selectedConv = null;

if ($selectedConvId > 0) {
    $stmt = $pdo->prepare("SELECT cc.*, u.name AS customer_name, u.email AS customer_email FROM chat_conversations cc JOIN users u ON cc.user_id = u.id WHERE cc.id = ?");
    $stmt->execute([$selectedConvId]);
    $selectedConv = $stmt->fetch();

    if ($selectedConv) {
        $stmt = $pdo->prepare("SELECT cm.*, u.name AS customer_name FROM chat_messages cm LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'customer' WHERE cm.conversation_id = ? ORDER BY cm.created_at ASC");
        $stmt->execute([$selectedConvId]);
        $messages = $stmt->fetchAll();

        // Mark as read
        $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'customer'")->execute([$selectedConvId]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat CS - Admin D'florist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
<style>
.chat-wrap { display:flex; height:calc(100vh - 160px); border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); margin-bottom:24px; }
.conv-list { width:240px; min-width:240px; background:#fff; border-right:1px solid #eee; display:flex; flex-direction:column; }
.conv-list-header { padding:14px 16px; border-bottom:1px solid #eee; font-weight:600; font-size:.9rem; }
.conv-list-body { flex:1; overflow-y:auto; }
.conv-item { padding:12px 16px; border-bottom:1px solid #f5f5f5; cursor:pointer; transition:.15s; }
.conv-item:hover { background:#FFF0F6; }
.conv-item.active { background:#FFD6E8; }
.conv-item .name { font-weight:600; font-size:.85rem; margin-bottom:2px; }
.conv-item .email { font-size:.75rem; color:#888; }
.conv-item .time { font-size:.7rem; color:#aaa; }
.chat-area { flex:1; display:flex; flex-direction:column; background:#f8f9fa; }
.chat-header { background:#fff; padding:14px 20px; border-bottom:1px solid #eee; }
.chat-header .cname { font-weight:600; font-size:.95rem; }
.chat-header .cemail { font-size:.78rem; color:#888; }
.chat-messages { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:6px; }
.msg { display:flex; }
.msg.customer { justify-content:flex-start; }
.msg.admin, .msg.system { justify-content:flex-end; }
.msg { display:flex; align-items:flex-end; }
.msg.customer { justify-content:flex-start; }
.msg.admin, .msg.system { justify-content:flex-end; }
.bubble { padding:8px 14px; border-radius:18px; font-size:.875rem; line-height:1.5; word-wrap:break-word; white-space:pre-wrap; display:inline-block; }
.msg.customer .bubble { background:#fff; border:1px solid #dee2e6; color:#2c3e50; border-bottom-left-radius:4px; }
.msg.admin .bubble { background:#FFD6E8; color:#2c3e50; border-bottom-right-radius:4px; }
.msg.system .bubble { background:#fff3cd; border:1px solid #ffc107; color:#856404; font-size:.8rem; border-radius:18px; }
.msg-meta { font-size:.72rem; color:#aaa; margin-top:3px; padding:0 4px; }
.msg.admin .msg-meta, .msg.system .msg-meta { text-align:right; }
.chat-input { background:#fff; padding:14px 16px; border-top:1px solid #eee; }
.chat-input textarea { resize:none; border-radius:20px; font-size:.85rem; padding:8px 16px; }
.chat-input .btn-send { border-radius:50%; width:40px; height:40px; padding:0; background:#FF69B4; border:none; color:#fff; flex-shrink:0; }
.chat-input .btn-send:hover { background:#e05fa0; }
.empty-state { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#bbb; gap:10px; }
.unread-badge { background:#FF69B4; color:#fff; border-radius:50%; width:18px; height:18px; font-size:10px; display:inline-flex; align-items:center; justify-content:center; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h4 fw-bold mb-0"><i class="fas fa-comments me-2"></i>Chat Customer Service</h1>
            </div>

            <div class="chat-wrap">
        <!-- Daftar Percakapan -->
        <div class="conv-list">
            <div class="conv-list-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-inbox me-1"></i> Percakapan
                    <span class="badge bg-secondary ms-1"><?php echo count($conversations); ?></span>
                </span>
                <div class="d-flex align-items-center gap-1">
                    <input type="text" id="convSearch"
                           class="form-control form-control-sm"
                           placeholder="Cari nama..."
                           style="width:0;opacity:0;transition:width .2s,opacity .2s;padding:0;border:none;font-size:.8rem;outline:none"
                           oninput="filterConv(this.value)">
                    <button class="btn btn-sm p-1" style="color:#888;line-height:1;background:none;border:none" onclick="toggleSearch()" title="Cari">
                        <i class="fas fa-search" style="font-size:.8rem"></i>
                    </button>
                </div>
            </div>
            <div class="conv-list-body">
                <?php if (empty($conversations)): ?>
                <div class="p-4 text-center text-muted small">
                    <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                    Belum ada percakapan
                </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <div class="conv-item <?php echo $conv['id'] == $selectedConvId ? 'active' : ''; ?>"
                     onclick="location.href='chats.php?conv=<?php echo $conv['id']; ?>'">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="name"><?php echo htmlspecialchars($conv['customer_name']); ?></div>
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($conv['unread'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                            <?php endif; ?>
                            <span class="time"><?php echo $conv['last_message_at'] ? date('H:i', strtotime($conv['last_message_at'])) : ''; ?></span>
                        </div>
                    </div>
                    <div class="email"><?php echo htmlspecialchars($conv['customer_email']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Area Chat -->
        <div class="chat-area">
            <?php if ($selectedConv): ?>
            <div class="chat-header">
                <div class="cname"><?php echo htmlspecialchars($selectedConv['customer_name']); ?></div>
                <div class="cemail"><?php echo htmlspecialchars($selectedConv['customer_email']); ?></div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                <div class="empty-state"><i class="fas fa-comment-dots fa-2x"></i><span>Belum ada pesan</span></div>
                <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <div class="msg <?php echo $msg['sender_type']; ?>">
                    <div style="display:flex;flex-direction:column;max-width:70%;<?php echo $msg['sender_type']==='admin'?'align-items:flex-end':'align-items:flex-start'; ?>">
                        <div class="bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        <div class="msg-meta">
                            <?php echo $msg['sender_type'] === 'customer' ? htmlspecialchars($msg['customer_name'] ?? 'Customer') : ($msg['sender_type'] === 'admin' ? 'Admin' : 'Sistem'); ?>
                            &middot; <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input">
                <form onsubmit="return sendMsg(event)" class="d-flex gap-2 align-items-end">
                    <textarea id="msgInput" class="form-control" rows="2"
                              placeholder="Ketik balasan..."
                              onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('sendBtn').click()}"
                              required></textarea>
                    <button type="submit" id="sendBtn" class="btn-send">
                        <i class="fas fa-paper-plane" style="font-size:.85rem"></i>
                    </button>
                </form>
                <small class="text-muted d-block mt-1" style="font-size:.7rem">Enter kirim · Shift+Enter baris baru</small>
            </div>

            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments fa-3x"></i>
                <span>Pilih percakapan untuk mulai membalas</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div><!-- chat-wrap -->
        </main>
    </div><!-- row -->
</div><!-- container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const convId = <?php echo $selectedConvId; ?>;
const chatMessages = document.getElementById('chatMessages');

function scrollBottom() {
    if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollBottom();

function sendMsg(e) {
    e.preventDefault();
    const input = document.getElementById('msgInput');
    const m = input.value.trim();
    if (!m || !convId) return false;
    input.disabled = true;
    fetch('../api/admin_send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({conversation_id: convId, message: m})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            input.value = '';
            loadMessages();
        } else {
            alert('Gagal mengirim pesan');
        }
    }).finally(() => { input.disabled = false; input.focus(); });
    return false;
}

let lastId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;

function loadMessages() {
    if (!convId) return;
    fetch('../api/get_messages.php?conversation_id=' + convId + '&last_id=' + lastId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'msg ' + msg.sender_type;
                    const sender = msg.sender_type === 'customer' ? (msg.customer_name || 'Customer') : (msg.sender_type === 'admin' ? 'Admin' : 'Sistem');
                    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
                    const align = msg.sender_type === 'admin' ? 'align-items:flex-end' : 'align-items:flex-start';
                    div.innerHTML = `<div style="display:flex;flex-direction:column;max-width:70%;${align}"><div class="bubble">${msg.message.replace(/\n/g,'<br>')}</div><div class="msg-meta">${sender} &middot; ${time}</div></div>`;
                    chatMessages.appendChild(div);
                    lastId = msg.id;
                });
                scrollBottom();
            }
        }).catch(() => {});
}

// Auto-refresh setiap 3 detik
if (convId) setInterval(loadMessages, 3000);

function toggleSearch() {
    const inp = document.getElementById('convSearch');
    const open = inp.style.width === '140px';
    inp.style.width = open ? '0' : '140px';
    inp.style.opacity = open ? '0' : '1';
    inp.style.padding = open ? '0' : '';
    inp.style.border = open ? 'none' : '1px solid #ddd';
    if (!open) { inp.focus(); } else { inp.value=''; filterConv(''); }
}

function filterConv(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(el => {
        const name = el.querySelector('.name')?.textContent.toLowerCase() || '';
        el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
}
</script>
</body>
</html>
