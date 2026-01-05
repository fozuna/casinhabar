<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Models\Account;
$deleted = Account::deleteByDirection('despesa');
echo "Despesas deletadas: " . $deleted;
