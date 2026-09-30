<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RatingRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO ratings (ticket_id, user_id, score, feedback) 
            VALUES (:ticket_id, :user_id, :score, :feedback)
        ");
        return $stmt->execute([
            'ticket_id' => $data['ticket_id'],
            'user_id'   => $data['user_id'],
            'score'     => $data['score'],
            'feedback'  => $data['feedback'] ?? null
        ]);
    }

    public function getByTicketId(int $ticketId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ratings WHERE ticket_id = :ticket_id LIMIT 1");
        $stmt->execute(['ticket_id' => $ticketId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}