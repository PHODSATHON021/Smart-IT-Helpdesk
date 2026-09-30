<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function login(string $email, string $password): bool
    {
        return $this->auth->login($email, $password);
    }

    public function logout(): void
    {
        $this->auth->logout();
    }

    public function getCurrentUser(): ?array
    {
        return $this->auth->user();
    }
}