<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class TicketRepository implements RepositoryInterface {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM tickets ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $sql = "INSERT INTO tickets (user_id, category_id, title, description, priority, status) 
                VALUES (:user_id, :category_id, :title, :description, :priority, 'open')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'     => $data['user_id'],
            ':category_id' => $data['category_id'],
            ':title'       => $data['title'],
            ':description' => $data['description'],
            ':priority'    => $data['priority'] ?? 'medium'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status' => $data['status'],
            ':id'     => $id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM tickets WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool
{
    $stmt = $this->db->prepare("UPDATE tickets SET status = :status WHERE id = :id");
    return $stmt->execute(['status' => $status, 'id' => $id]);
}
}