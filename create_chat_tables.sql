-- Create Chat Tables for D'Florist

-- Drop existing tables if any
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_conversations;

-- Tabel Chat Conversations
CREATE TABLE chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Chat Messages
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_type ENUM('customer', 'admin', 'system') NOT NULL,
    sender_id INT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_conversation_user ON chat_conversations(user_id);
CREATE INDEX idx_conversation_status ON chat_conversations(status);
CREATE INDEX idx_message_conversation ON chat_messages(conversation_id);
CREATE INDEX idx_message_sender ON chat_messages(sender_type, sender_id);
CREATE INDEX idx_message_read ON chat_messages(is_read);
