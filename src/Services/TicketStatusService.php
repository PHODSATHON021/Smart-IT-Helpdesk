<?php

namespace App\Services;

use App\Repositories\TicketRepository;
use App\Core\EventDispatcher;

class TicketStatusService
{
    private TicketRepository $repo;
    private EventDispatcher $events;

    // แก้ไข Parameter ตัวที่ 2 จาก TicketObserver เป็น EventDispatcher $events
    public function __construct(TicketRepository $repo, EventDispatcher $events)
    {
        $this->repo = $repo;
        $this->events = $events;
    }

    public function transition(int $ticketId, string $newStatus, array $actor, string $userEmail): void
    {
        $ticket = $this->repo->find($ticketId);
        if (!$ticket) {
            throw new \Exception("Ticket not found.");
        }

        $fromStatus = $ticket['status'];

        // บันทึกการอัปเดตสถานะ
        $this->repo->update($ticketId, ['status' => $newStatus]);

        // Dispatch Event แจ้งเตือนส่งผ่าน Email (Observer Pattern)
        $this->events->dispatch('ticket.status_changed', [
            'ticket' => $ticket,
            'from'   => $fromStatus,
            'to'     => $newStatus,
            'email'  => $userEmail
        ]);
    }
}