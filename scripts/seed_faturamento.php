<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Core\Database;
$pdo = Database::getConnection();

// Ensure cost center "Administrativo"
$ccName = 'Administrativo';
$stmt = $pdo->prepare('SELECT id FROM cost_centers WHERE name = ? LIMIT 1');
$stmt->execute([$ccName]);
$ccId = (int)($stmt->fetchColumn() ?: 0);
if ($ccId === 0) {
  $pdo->prepare('INSERT INTO cost_centers (name, description) VALUES (?, ?)')->execute([$ccName, 'Centro administrativo']);
  $ccId = (int)$pdo->lastInsertId();
}

// Ensure account type "Faturamento" (receita)
$typeName = 'Faturamento';
$stmt = $pdo->prepare('SELECT id FROM account_types WHERE name = ? AND kind = "receita" LIMIT 1');
$stmt->execute([$typeName]);
$typeId = (int)($stmt->fetchColumn() ?: 0);
if ($typeId === 0) {
  $pdo->prepare('INSERT INTO account_types (name, kind, cost_center_id) VALUES (?, "receita", ?)')->execute([$typeName, $ccId]);
  $typeId = (int)$pdo->lastInsertId();
}
echo "Seed concluído: CC={$ccId}, Tipo={$typeId}";

