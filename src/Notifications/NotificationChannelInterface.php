<?php

namespace App\Notifications;

interface NotificationChannelInterface
{
    public function send(string $message, array $context = []): bool;
}