<?php
namespace App\Services;
use App\Core\Database;
use PDO;

class Reports
{
    public static function summary(?string $start, ?string $end, ?int $costCenterId): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $where = [];
        if ($start) { $where[] = 'i.due_date >= ?'; $params[] = $start; }
        if ($end) { $where[] = 'i.due_date <= ?'; $params[] = $end; }
        if ($costCenterId) { $where[] = 'at.cost_center_id = ?'; $params[] = $costCenterId; }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sqlR = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND at.kind="receita"';
        $sqlD = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND at.kind="despesa"';
        $stmtR = $pdo->prepare($sqlR);
        $stmtD = $pdo->prepare($sqlD);
        $stmtR->execute($params);
        $stmtD->execute($params);
        $receitasPeriodo = (float)$stmtR->fetchColumn();
        $despesasPeriodo = (float)$stmtD->fetchColumn();
        $saldoPeriodo = $receitasPeriodo - $despesasPeriodo;
        $sqlPaidR = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id WHERE i.status="paid" AND i.paid_at <= NOW() AND at.kind="receita"';
        $sqlPaidD = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id WHERE i.status="paid" AND i.paid_at <= NOW() AND at.kind="despesa"';
        $paidR = (float)$pdo->query($sqlPaidR)->fetchColumn();
        $paidD = (float)$pdo->query($sqlPaidD)->fetchColumn();
        $saldoAteMomento = $paidR - $paidD;
        return [
            'receitasPeriodo' => $receitasPeriodo,
            'despesasPeriodo' => $despesasPeriodo,
            'saldoPeriodo' => $saldoPeriodo,
            'saldoAteMomento' => $saldoAteMomento,
        ];
    }

    public static function seriesDaily(?string $start, ?string $end, ?int $costCenterId): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $where = [];
        if ($start) { $where[] = 'i.due_date >= ?'; $params[] = $start; }
        if ($end) { $where[] = 'i.due_date <= ?'; $params[] = $end; }
        if ($costCenterId) { $where[] = 'at.cost_center_id = ?'; $params[] = $costCenterId; }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sqlR = 'SELECT DATE(i.due_date) d, SUM(i.amount) v FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND at.kind="receita" GROUP BY DATE(i.due_date) ORDER BY DATE(i.due_date)';
        $sqlD = 'SELECT DATE(i.due_date) d, SUM(i.amount) v FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND at.kind="despesa" GROUP BY DATE(i.due_date) ORDER BY DATE(i.due_date)';
        $stmtR = $pdo->prepare($sqlR); $stmtR->execute($params); $rowsR = $stmtR->fetchAll(PDO::FETCH_ASSOC);
        $stmtD = $pdo->prepare($sqlD); $stmtD->execute($params); $rowsD = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        $labels = [];
        $mapR = [];
        $mapD = [];
        foreach ($rowsR as $r) { $labels[$r['d']] = true; $mapR[$r['d']] = (float)$r['v']; }
        foreach ($rowsD as $d) { $labels[$d['d']] = true; $mapD[$d['d']] = (float)$d['v']; }
        $labels = array_keys($labels); sort($labels);
        $receitas = []; $despesas = [];
        foreach ($labels as $l) { $receitas[] = (float)($mapR[$l] ?? 0); $despesas[] = (float)($mapD[$l] ?? 0); }
        return ['labels' => $labels, 'receitas' => $receitas, 'despesas' => $despesas];
    }

    public static function flowSplit(?string $start, ?string $end, ?int $costCenterId): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $where = [];
        if ($start) { $where[] = 'i.due_date >= ?'; $params[] = $start; }
        if ($end) { $where[] = 'i.due_date <= ?'; $params[] = $end; }
        if ($costCenterId) { $where[] = 'at.cost_center_id = ?'; $params[] = $costCenterId; }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sqlPR = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND i.status="pending" AND at.kind="receita"';
        $sqlPD = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND i.status="pending" AND at.kind="despesa"';
        $sqlRR = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND i.status="paid" AND at.kind="receita"';
        $sqlRD = 'SELECT COALESCE(SUM(i.amount),0) FROM installments i JOIN accounts a ON a.id=i.account_id JOIN account_types at ON at.id=a.account_type_id ' . $w . ' AND i.status="paid" AND at.kind="despesa"';
        $stmtPR = $pdo->prepare($sqlPR); $stmtPR->execute($params); $pendingReceitas = (float)$stmtPR->fetchColumn();
        $stmtPD = $pdo->prepare($sqlPD); $stmtPD->execute($params); $pendingDespesas = (float)$stmtPD->fetchColumn();
        $stmtRR = $pdo->prepare($sqlRR); $stmtRR->execute($params); $paidReceitas = (float)$stmtRR->fetchColumn();
        $stmtRD = $pdo->prepare($sqlRD); $stmtRD->execute($params); $paidDespesas = (float)$stmtRD->fetchColumn();
        return [
            'pendingReceitas' => $pendingReceitas,
            'pendingDespesas' => $pendingDespesas,
            'paidReceitas' => $paidReceitas,
            'paidDespesas' => $paidDespesas,
        ];
    }

    public static function flowItems(?string $start, ?string $end, ?int $costCenterId): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $where = [];
        if ($start) { $where[] = 'i.due_date >= ?'; $params[] = $start; }
        if ($end) { $where[] = 'i.due_date <= ?'; $params[] = $end; }
        if ($costCenterId) { $where[] = 'at.cost_center_id = ?'; $params[] = $costCenterId; }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = 'SELECT i.id, i.number, i.due_date, i.amount, i.status, i.paid_at,
                       a.description, a.document,
                       at.kind, at.name AS account_type_name,
                       cc.name AS cost_center_name,
                       CASE WHEN a.party_type = "customer" THEN c.name WHEN a.party_type = "supplier" THEN s.name ELSE NULL END AS party_name
                FROM installments i
                JOIN accounts a ON a.id = i.account_id
                JOIN account_types at ON at.id = a.account_type_id
                JOIN cost_centers cc ON cc.id = at.cost_center_id
                LEFT JOIN customers c ON (a.party_type = "customer" AND a.party_id = c.id)
                LEFT JOIN suppliers s ON (a.party_type = "supplier" AND a.party_id = s.id)
                ' . $w . '
                ORDER BY i.due_date ASC, i.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

