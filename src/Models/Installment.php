<?php
namespace App\Models;
use App\Core\Database;
use PDO;

class Installment
{
    public static function byAccount(int $accountId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM installments WHERE account_id = ? ORDER BY number');
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markPaid(int $installmentId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE installments SET status = "paid", paid_at = NOW() WHERE id = ?');
        $stmt->execute([$installmentId]);
        $check = $pdo->prepare('SELECT account_id FROM installments WHERE id = ?');
        $check->execute([$installmentId]);
        $accountId = (int)$check->fetchColumn();
        $pending = $pdo->prepare('SELECT COUNT(*) FROM installments WHERE account_id = ? AND status = "pending"');
        $pending->execute([$accountId]);
        if ((int)$pending->fetchColumn() === 0) {
            $pdo->prepare('UPDATE accounts SET status = "closed" WHERE id = ?')->execute([$accountId]);
        }
    }

    public static function markPaidWithDate(int $installmentId, string $date): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE installments SET status = "paid", paid_at = ? WHERE id = ?');
        $stmt->execute([$date . ' 00:00:00', $installmentId]);
        $check = $pdo->prepare('SELECT account_id FROM installments WHERE id = ?');
        $check->execute([$installmentId]);
        $accountId = (int)$check->fetchColumn();
        $pending = $pdo->prepare('SELECT COUNT(*) FROM installments WHERE account_id = ? AND status = "pending"');
        $pending->execute([$accountId]);
        if ((int)$pending->fetchColumn() === 0) {
            $pdo->prepare('UPDATE accounts SET status = "closed" WHERE id = ?')->execute([$accountId]);
        }
    }
}

