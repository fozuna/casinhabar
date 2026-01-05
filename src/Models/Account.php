<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class Account
{
    public static function create(int $accountTypeId, string $partyType, int $partyId, string $description, float $totalAmount, int $installments, string $firstDueDate, ?string $document = null, int $imported = 0, ?string $importBatch = null): int
    {
        $pdo = Database::getConnection();
        $kindStmt = $pdo->prepare('SELECT kind FROM account_types WHERE id = ?');
        $kindStmt->execute([$accountTypeId]);
        $kind = $kindStmt->fetchColumn();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO accounts (account_type_id, party_type, party_id, description, total_amount, due_start_date, direction, document, imported, import_batch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$accountTypeId, $partyType, $partyId, $description, $totalAmount, $firstDueDate, $kind, $document, $imported, $importBatch]);
        $accountId = (int)$pdo->lastInsertId();
        $per = round($totalAmount / $installments, 2);
        $sum = 0.0;
        $date = new \DateTime($firstDueDate);
        for ($i = 1; $i <= $installments; $i++) {
            $amt = ($i < $installments) ? $per : round($totalAmount - $sum, 2);
            $sum += $amt;
            $stmtI = $pdo->prepare('INSERT INTO installments (account_id, number, due_date, amount) VALUES (?, ?, ?, ?)');
            $stmtI->execute([$accountId, $i, $date->format('Y-m-d'), $amt]);
            $date->modify('+1 month');
        }
        $pdo->commit();
        return $accountId;
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT a.*, at.name AS account_type_name, at.kind, cc.name AS cost_center_name,
                CASE WHEN a.party_type = "customer" THEN c.name ELSE s.name END AS party_name
                FROM accounts a
                JOIN account_types at ON at.id = a.account_type_id
                JOIN cost_centers cc ON cc.id = at.cost_center_id
                LEFT JOIN customers c ON (a.party_type = "customer" AND c.id = a.party_id)
                LEFT JOIN suppliers s ON (a.party_type = "supplier" AND s.id = a.party_id)
                ORDER BY a.id DESC';
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function update(int $id, string $description, ?string $document): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE accounts SET description = ?, document = ? WHERE id = ?');
        $stmt->execute([$description, $document, $id]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM installments WHERE account_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM accounts WHERE id = ?')->execute([$id]);
    }

    public static function deleteImported(string $direction, ?string $batchId = null, ?string $start = null, ?string $end = null): int
    {
        $pdo = Database::getConnection();
        $where = ['imported = 1', 'direction = ?'];
        $params = [$direction];
        if ($batchId) { $where[] = 'import_batch = ?'; $params[] = $batchId; }
        if ($start) { $where[] = 'due_start_date >= ?'; $params[] = $start; }
        if ($end) { $where[] = 'due_start_date <= ?'; $params[] = $end; }
        $sqlIds = 'SELECT id FROM accounts WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sqlIds);
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $deleted = 0;
        foreach ($ids as $aid) {
            $pdo->prepare('DELETE FROM installments WHERE account_id = ?')->execute([$aid]);
            $pdo->prepare('DELETE FROM accounts WHERE id = ?')->execute([$aid]);
            $deleted++;
        }
        return $deleted;
    }
}

