# Fitur Live Chat Customer Service - D'florist

## Overview
Sistem live chat memungkinkan customer berkomunikasi langsung dengan admin/customer service untuk mendapatkan bantuan real-time.

## Fitur Chat

### Customer Side (`chat.php`)

#### 1. **Auto-Create Conversation**
- Saat customer pertama kali membuka chat, sistem otomatis membuat conversation baru
- Auto-greeting dari sistem dengan informasi bantuan

#### 2. **Real-time Messaging**
- Customer dapat mengirim pesan ke admin
- Polling setiap 3 detik untuk pesan baru dari admin
- Notifikasi visual untuk pesan baru

#### 3. **User Interface**
- Clean chat interface dengan bubble messages
- Customer messages: Gradient pink-blue (kanan)
- Admin messages: White bubble (kiri)
- System messages: Yellow bubble (center)
- Timestamp untuk setiap pesan
- Online status indicator

#### 4. **Input Features**
- Textarea dengan auto-resize
- Enter untuk kirim, Shift+Enter untuk baris baru
- Send button dengan icon
- Character limit ready

#### 5. **Chat History**
- Semua pesan tersimpan di database
- Scroll otomatis ke pesan terbaru
- Load history saat membuka chat

### Admin Side (`admin/chats.php`)

#### 1. **Conversation List**
- Daftar semua percakapan aktif
- Unread message counter per conversation
- Last message preview
- Timestamp last message
- Search functionality

#### 2. **Chat Interface**
- Split view: Conversation list (kiri) + Chat area (kanan)
- Customer info di header (nama, email, phone)
- Real-time messaging dengan polling
- Mark as read otomatis

#### 3. **Admin Features**
- Reply ke customer
- Close conversation
- View customer details
- Multiple conversation handling

#### 4. **Notifications**
- Total unread count di header
- Unread badge per conversation
- Visual indicator untuk pesan baru

## Database Schema

### Table: `chat_conversations`
```sql
CREATE TABLE chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Table: `chat_messages`
```sql
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_id INT,
    sender_type ENUM('customer', 'admin', 'system') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);
```

## API Endpoints

### 1. `api/send_message.php` (Customer)
**Method:** POST  
**Auth:** Customer session required  
**Body:**
```json
{
    "conversation_id": 1,
    "message": "Halo, saya mau tanya..."
}
```
**Response:**
```json
{
    "success": true,
    "message": "Message sent",
    "message_id": 123
}
```

### 2. `api/get_messages.php` (Customer)
**Method:** GET  
**Auth:** Customer session required  
**Params:** `conversation_id`, `last_id`  
**Response:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 124,
            "message": "Halo, ada yang bisa dibantu?",
            "sender_type": "admin",
            "admin_name": "Admin CS",
            "created_at": "2024-01-01 10:00:00"
        }
    ]
}
```

### 3. `api/admin_send_message.php` (Admin)
**Method:** POST  
**Auth:** Admin session required  
**Body:**
```json
{
    "conversation_id": 1,
    "message": "Halo, ada yang bisa dibantu?"
}
```

### 4. `api/admin_get_messages.php` (Admin)
**Method:** GET  
**Auth:** Admin session required  
**Params:** `conversation_id`, `last_id`

### 5. `api/close_conversation.php` (Admin)
**Method:** POST  
**Auth:** Admin session required  
**Body:**
```json
{
    "conversation_id": 1
}
```

## Flow Diagram

### Customer Flow:
```
1. Customer login
2. Click "Chat CS" di navbar
3. System check existing conversation
   - If not exist: Create new + send auto-greeting
   - If exist: Load conversation
4. Customer send message
5. Message saved to database
6. Poll for admin reply every 3 seconds
7. Display new messages
```

### Admin Flow:
```
1. Admin login
2. Go to "Chat CS" menu
3. View all active conversations
4. Click conversation to open
5. View customer info & chat history
6. Reply to customer
7. Poll for new customer messages
8. Close conversation when done
```

## Features Comparison with Alfagift

| Feature | Alfagift | D'florist | Status |
|---------|----------|-----------|--------|
| Live Chat | ✅ | ✅ | Implemented |
| Real-time Messaging | ✅ | ✅ Polling | Similar |
| Chat History | ✅ | ✅ | Same |
| Multiple Conversations | ✅ | ✅ | Same |
| Unread Indicator | ✅ | ✅ | Same |
| File Upload | ✅ | ❌ | Not yet |
| Emoji Support | ✅ | ❌ | Not yet |
| Typing Indicator | ✅ | ⚠️ UI only | Partial |
| Push Notification | ✅ | ❌ | Not yet |
| Chat Bot | ✅ | ⚠️ Auto-greeting | Partial |

## Styling

### Colors:
- Customer bubble: Gradient pink-blue (`var(--pink-dark)` to `var(--blue-dark)`)
- Admin bubble: White with border
- System bubble: Yellow (`#fff3cd`)
- Online indicator: Green with pulse animation

### Responsive:
- Desktop: Split view (list + chat)
- Mobile: Full width chat, back button to list

## Security

1. **Authentication:**
   - Customer must be logged in
   - Admin must be logged in
   - Session-based authentication

2. **Authorization:**
   - Customer can only access their own conversations
   - Admin can access all conversations
   - Verify conversation ownership in API

3. **Input Sanitization:**
   - All messages sanitized with `htmlspecialchars()`
   - XSS protection
   - SQL injection protection (prepared statements)

4. **Rate Limiting:**
   - Ready for implementation
   - Prevent spam messages

## Performance

### Polling Interval:
- Customer: 3 seconds
- Admin: 3 seconds

### Optimization:
- Only fetch new messages (last_id filter)
- Mark as read in batch
- Index on conversation_id and created_at

### Future Improvements:
1. **WebSocket** - Replace polling with real-time WebSocket
2. **Push Notification** - Notify when offline
3. **File Upload** - Send images/documents
4. **Emoji Picker** - Add emoji support
5. **Typing Indicator** - Real typing indicator
6. **Chat Bot** - Auto-reply for common questions
7. **Chat Analytics** - Response time, satisfaction rating
8. **Mobile App** - Better mobile experience

## Usage Examples

### Customer Usage:
```
1. Login ke akun
2. Klik "Chat CS" di navbar
3. Ketik pertanyaan: "Apakah bunga mawar tersedia?"
4. Tekan Enter atau klik tombol kirim
5. Tunggu balasan dari admin
6. Lanjutkan percakapan
```

### Admin Usage:
```
1. Login ke admin panel
2. Klik "Chat CS" di sidebar
3. Lihat daftar percakapan (unread badge)
4. Klik percakapan untuk membuka
5. Baca pesan customer
6. Ketik balasan
7. Tekan Enter atau klik Kirim
8. Tutup percakapan jika selesai
```

## Testing Checklist

### Customer Side:
- [ ] Create new conversation
- [ ] Send message
- [ ] Receive admin reply
- [ ] View chat history
- [ ] Auto-scroll to bottom
- [ ] Enter key to send
- [ ] Shift+Enter for new line
- [ ] Textarea auto-resize

### Admin Side:
- [ ] View all conversations
- [ ] Unread count display
- [ ] Open conversation
- [ ] View customer info
- [ ] Send reply
- [ ] Receive customer message
- [ ] Close conversation
- [ ] Search conversations

### API:
- [ ] Authentication check
- [ ] Authorization check
- [ ] Message validation
- [ ] Error handling
- [ ] Polling performance

## Troubleshooting

### Messages not appearing:
- Check polling interval (3 seconds)
- Check browser console for errors
- Verify API endpoints are accessible
- Check database connection

### Unread count not updating:
- Check mark_as_read query
- Verify conversation_id is correct
- Check database triggers

### Performance issues:
- Reduce polling interval
- Add database indexes
- Implement caching
- Consider WebSocket

## Credits

Fitur chat ini terinspirasi dari:
- **Alfagift** - Live chat UX/UI
- **WhatsApp Web** - Chat bubble design
- **Facebook Messenger** - Conversation list layout

---

**Note:** Fitur ini adalah implementasi dasar live chat. Untuk production, disarankan menggunakan WebSocket (Socket.io) untuk real-time communication yang lebih efisien.
