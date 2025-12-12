<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Models\User;
use App\Core\Database;
$pdo = Database::getConnection();
$email = getenv('ADMIN_EMAIL') ?: 'admin@local';
$password = getenv('ADMIN_PASSWORD') ?: 'admin123';
$exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$exists->execute([$email]);
if ($exists->fetchColumn() == 0) {
    User::create('Administrador', $email, $password, 'admin');
    echo 'Admin criado: ' . $email;
} else {
    echo 'Admin já existe';
}

