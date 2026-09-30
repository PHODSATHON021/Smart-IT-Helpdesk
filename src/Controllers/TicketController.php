<?php

namespace App\Controllers;

use App\Repositories\TicketRepository;
use App\Services\TicketStatusService;
use App\Services\AuthService;
use App\Core\Database;

class TicketController
{
    private TicketRepository $ticketRepo;
    private TicketStatusService $statusService;
    private AuthService $auth;

    public function __construct(
        TicketRepository $ticketRepo,
        TicketStatusService $statusService,
        AuthService $auth
    ) {
        $this->ticketRepo = $ticketRepo;
        $this->statusService = $statusService;
        $this->auth = $auth;
    }

    public function index(): array
    {
        $this->auth->requireLogin();
        $currentUser = $this->auth->user();
        $db = Database::getInstance()->getConnection();

        if (in_array($currentUser['role'], ['admin', 'technician'])) {
            $tickets = $db->query("
                SELECT t.*, c.name AS category_name, u.name AS user_name 
                FROM tickets t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $stmt = $db->prepare("
                SELECT t.*, c.name AS category_name, u.name AS user_name 
                FROM tickets t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.user_id = :user_id
                ORDER BY t.created_at DESC
            ");
            $stmt->execute(['user_id' => $currentUser['id']]);
            $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $categories = $db->query("SELECT * FROM categories")->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'currentUser' => $currentUser,
            'tickets'     => $tickets,
            'categories'  => $categories
        ];
    }

    public function create(array $data): ?int
    {
        $this->auth->requireLogin();
        $currentUser = $this->auth->user();

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 1);
        $priority = $data['priority'] ?? 'Medium';

        if (empty($title)) {
            return null;
        }

        return $this->ticketRepo->create([
            'user_id'     => $currentUser['id'],
            'category_id' => $categoryId,
            'title'       => $title,
            'description' => $description,
            'priority'    => $priority,
            'status'      => 'Open'
        ]);
    }

    public function updateStatus(int $ticketId, string $newStatus): void
    {
        $this->auth->requireLogin();
        $currentUser = $this->auth->user();

        if (in_array($currentUser['role'], ['admin', 'technician'])) {
            $this->statusService->transition($ticketId, $newStatus, $currentUser, $currentUser['email'] ?? '');
        }
    }
}