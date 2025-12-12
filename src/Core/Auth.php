<?php
namespace App\Core;
use App\Models\User;

class Auth
{
    public static function login(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password_hash'])) return false;
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }

    public static function logout(): void
    {
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireRole(array $roles): bool
    {
        $role = $_SESSION['user_role'] ?? null;
        return $role && in_array($role, $roles, true);
    }
}

