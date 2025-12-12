<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class CostCenter
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT * FROM cost_centers WHERE active = 1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT * FROM cost_centers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(string $name, ?string $description): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO cost_centers (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE cost_centers SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description, $id]);
    }

    public static function toggle(int $id, bool $active): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE cost_centers SET active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $dep = $pdo->prepare('SELECT COUNT(*) FROM account_types WHERE cost_center_id = ?');
        $dep->execute([$id]);
        if ((int)$dep->fetchColumn() > 0) return false;
        $pdo->prepare('DELETE FROM cost_centers WHERE id = ?')->execute([$id]);
        return true;
    }
}

