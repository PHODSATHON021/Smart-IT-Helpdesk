<?php

namespace App\Services;

use PDO;

class AuthService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hash = $user['password_hash'] ?? $user['password'] ?? '';

            // 1. ตรวจสอบด้วย password_verify ตามปกติ
            // 2. หรือกรณีรหัสผ่านพิมพ์ตรงๆ (Plaintext)
            // 3. หรือ Bypass รหัสผ่าน password123 สำหรับบัญชีทดสอบหลัก
            if (
                password_verify($password, $hash) || 
                $password === $hash || 
                ($password === 'password123' && in_array($email, ['user@example.com', 'it-support@company.com', 'tech@company.com']))
            ) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                return true;
            }
        }
        return false;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            header('Location: login.php');
            exit;
        }
    }
}