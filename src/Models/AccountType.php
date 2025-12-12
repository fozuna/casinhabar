<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class AccountType
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT at.*, cc.name AS cost_center_name FROM account_types at JOIN cost_centers cc ON cc.id = at.cost_center_id ORDER BY at.name';
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(string $name, string $kind, int $costCenterId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO account_types (name, kind, cost_center_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $kind, $costCenterId]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, string $name, string $kind, int $costCenterId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE account_types SET name = ?, kind = ?, cost_center_id = ? WHERE id = ?');
        $stmt->execute([$name, $kind, $costCenterId, $id]);
    }

    public static function toggle(int $id, bool $active): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE account_types SET active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $dep = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE account_type_id = ?');
        $dep->execute([$id]);
        if ((int)$dep->fetchColumn() > 0) return false;
        $pdo->prepare('DELETE FROM account_types WHERE id = ?')->execute([$id]);
        return true;
    }
}

