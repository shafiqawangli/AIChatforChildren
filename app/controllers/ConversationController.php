<?php

namespace App\Controllers;

use Core\Database;
use PDO;

class ConversationController
{
    private $pdo;
    private $userId;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->userId = $_SESSION['user']['id'] ?? null;
    }

    /**
     * Send JSON response
     */
    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get JSON input
     */
    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * List all conversations for current user
     */
    public function list()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $stmt = $this->pdo->prepare("
            SELECT id, title, auto_renamed, created_at, updated_at
            FROM conversations
            WHERE user_id = ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$this->userId]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['conversations' => $conversations]);
    }

    /**
     * Create a new conversation
     */
    public function create()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        $title = $input['title'] ?? 'New Chat';

        $stmt = $this->pdo->prepare("
            INSERT INTO conversations (user_id, title, auto_renamed)
            VALUES (?, ?, 0)
        ");
        $stmt->execute([$this->userId, $title]);

        $conversationId = $this->pdo->lastInsertId();

        $this->jsonResponse([
            'id' => $conversationId,
            'title' => $title,
            'auto_renamed' => false,
            'messages' => []
        ]);
    }

    /**
     * Get a single conversation with messages
     */
    public function get()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $conversationId = $_GET['id'] ?? null;
        if (!$conversationId) {
            $this->jsonResponse(['error' => 'Conversation ID required'], 400);
        }

        // Get conversation
        $stmt = $this->pdo->prepare("
            SELECT id, title, auto_renamed, created_at, updated_at
            FROM conversations
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$conversationId, $this->userId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        // Get messages
        $stmt = $this->pdo->prepare("
            SELECT id, role, content, created_at
            FROM messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conversation['messages'] = $messages;

        $this->jsonResponse($conversation);
    }

    /**
     * Update conversation (rename)
     */
    public function update()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        $conversationId = $input['id'] ?? null;
        $title = $input['title'] ?? null;
        $autoRenamed = isset($input['auto_renamed']) ? (int)$input['auto_renamed'] : null;

        if (!$conversationId) {
            $this->jsonResponse(['error' => 'Conversation ID required'], 400);
        }

        // Verify ownership
        $stmt = $this->pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversationId, $this->userId]);
        if (!$stmt->fetch()) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        // Build update query
        $updates = [];
        $params = [];

        if ($title !== null) {
            $updates[] = "title = ?";
            $params[] = $title;
        }
        if ($autoRenamed !== null) {
            $updates[] = "auto_renamed = ?";
            $params[] = $autoRenamed;
        }

        if (empty($updates)) {
            $this->jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $conversationId;
        $stmt = $this->pdo->prepare("UPDATE conversations SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Delete a conversation
     */
    public function delete()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        $conversationId = $input['id'] ?? null;

        if (!$conversationId) {
            $this->jsonResponse(['error' => 'Conversation ID required'], 400);
        }

        // Verify ownership and delete (messages will be cascade deleted)
        $stmt = $this->pdo->prepare("DELETE FROM conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversationId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        $this->jsonResponse(['success' => true]);
    }

    /**
     * Add a message to conversation
     */
    public function addMessage()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getJsonInput();
        $conversationId = $input['conversation_id'] ?? null;
        $role = $input['role'] ?? null;
        $content = $input['content'] ?? null;

        if (!$conversationId || !$role || !$content) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        if (!in_array($role, ['user', 'assistant', 'system'])) {
            $this->jsonResponse(['error' => 'Invalid role'], 400);
        }

        // Verify conversation ownership
        $stmt = $this->pdo->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversationId, $this->userId]);
        if (!$stmt->fetch()) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        // Insert message
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (conversation_id, role, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$conversationId, $role, $content]);

        // Update conversation's updated_at
        $stmt = $this->pdo->prepare("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$conversationId]);

        $this->jsonResponse([
            'id' => $this->pdo->lastInsertId(),
            'success' => true
        ]);
    }

    /**
     * Search conversations by title
     */
    public function search()
    {
        if (!$this->userId) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $query = $_GET['q'] ?? '';

        $stmt = $this->pdo->prepare("
            SELECT id, title, auto_renamed, created_at, updated_at
            FROM conversations
            WHERE user_id = ? AND title LIKE ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$this->userId, "%$query%"]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse(['conversations' => $conversations]);
    }
}
