<?php
namespace App\Models;
use App\Core\Database;
use App\Utils\Validators;
use PDO;

class Supplier
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT * FROM suppliers ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(string $name, string $cpfCnpj, ?string $email, ?string $phone): ?int
    {
        $doc = Validators::onlyDigits($cpfCnpj);
        $valid = Validators::isCPF($doc) || Validators::isCNPJ($doc);
        if (!$valid) return null;
        $pdo = Database::getConnection();
        $exists = $pdo->prepare('SELECT COUNT(*) FROM suppliers WHERE cpf_cnpj = ?');
        $exists->execute([$doc]);
        if ($exists->fetchColumn() > 0) return null;
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, cpf_cnpj, email, phone) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $doc, $email, $phone]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByName(string $name): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([trim($name)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createPlaceholder(string $name): int
    {
        $pdo = Database::getConnection();
        $tmp = 'TMP' . substr(preg_replace('/[^0-9]/','', (string)microtime(true)), -10);
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, cpf_cnpj, email, phone) VALUES (?, ?, NULL, NULL)');
        $stmt->execute([$name, $tmp]);
        return (int)$pdo->lastInsertId();
    }
}

