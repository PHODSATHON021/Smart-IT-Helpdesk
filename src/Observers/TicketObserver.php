<?php

namespace App\Observers;

use App\Notifications\NotificationChannelInterface;

class TicketObserver
{
    private NotificationChannelInterface $notifier;

    public function __construct(NotificationChannelInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function handleCreated(array $ticket, string $userEmail): void
    {
        $subject = "แจ้งซ่อมใหม่: Ticket #{$ticket['id']} - {$ticket['title']}";
        $message = "มีการสร้างรายการแจ้งซ่อมใหม่ในระบบ\n\n"
                 . "Ticket ID: #{$ticket['id']}\n"
                 . "หัวข้อ: {$ticket['title']}\n"
                 . "ความสำคัญ: {$ticket['priority']}\n";

        $this->notifier->send($message, [
            'to_email' => $userEmail,
            'subject'  => $subject
        ]);
    }

    public function handleStatusChanged(array $ticket, string $fromStatus, string $toStatus, string $userEmail): void
    {
        // ตรวจสอบว่ามี Email ถูกส่งมาจริงหรือไม่
        if (empty($userEmail)) {
            return;
        }

        $subject = "อัปเดตสถานะ Ticket #{$ticket['id']} เป็น {$toStatus}";
        $message = "รายการแจ้งซ่อมของคุณมีการอัปเดตสถานะ\n\n"
                 . "Ticket ID: #{$ticket['id']}\n"
                 . "หัวข้อ: {$ticket['title']}\n"
                 . "สถานะเดิม: {$fromStatus}\n"
                 . "สถานะใหม่: {$toStatus}\n";

        $this->notifier->send($message, [
            'to_email' => $userEmail,
            'subject'  => $subject
        ]);
    }
}