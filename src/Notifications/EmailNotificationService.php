<?php

namespace App\Notifications;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotificationService implements NotificationChannelInterface
{
    private string $host;
    private string $username;
    private string $password;
    private int $port;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->username = $_ENV['SMTP_USER'] ?? '';
        $this->password = $_ENV['SMTP_PASS'] ?? '';
        $this->port = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->fromAddress = $_ENV['SMTP_FROM_ADDRESS'] ?? $this->username;
        $this->fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Smart IT Helpdesk';
    }

    public function send(string $message, array $context = []): bool
    {
        $toEmail = $context['to_email'] ?? null;
        $subject = $context['subject'] ?? 'แจ้งเตือนระบบ Smart IT Helpdesk';

        if (!$toEmail) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = nl2br($message);
            $mail->AltBody = strip_tags($message);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
}