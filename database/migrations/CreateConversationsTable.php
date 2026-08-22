<?php

namespace Database\Migrations;

use Core\Migration;

class CreateConversationsTable extends Migration
{
    public function up()
    {
        // Create conversations table
        $sql = "CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT 'New Chat',
            auto_renamed TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_updated_at (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
        echo " Conversations table created successfully.\n";

        // Create messages table
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            role ENUM('user', 'assistant', 'system') NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            INDEX idx_conversation_id (conversation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
        echo " Messages table created successfully.\n";
    }

    public function down()
    {
        $this->pdo->exec("DROP TABLE IF EXISTS messages");
        echo " Messages table dropped.\n";

        $this->pdo->exec("DROP TABLE IF EXISTS conversations");
        echo " Conversations table dropped.\n";
    }
}
