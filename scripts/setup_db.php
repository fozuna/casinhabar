<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'casinha';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};charset={$charset}";
try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET {$charset} COLLATE {$charset}_general_ci");
  echo 'Database verificado/criado';
} catch (Throwable $e) {
  echo 'Erro ao criar banco';
}

