<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CommentRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    public function getByTicketId(int $ticketId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.role as user_role 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.ticket_id = :ticket_id 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        // Parameter มี 4 ตัว: :ticket_id, :user_id, :body, :image_path
        $stmt = $this->db->prepare("
            INSERT INTO comments (ticket_id, user_id, body, image_path, created_at)
            VALUES (:ticket_id, :user_id, :body, :image_path, NOW())
        ");

        // Array ที่ส่งให้ execute ต้องมี Key ครบตรงกันพอดีทั้ง 4 ตัว
        $stmt->execute([
            ':ticket_id'  => $data['ticket_id'],
            ':user_id'    => $data['user_id'],
            ':body'       => $data['comment'] ?? $data['message'] ?? $data['body'] ?? '',
            ':image_path' => $data['image_path'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }
}