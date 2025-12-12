<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Core\Database;
$pdo = Database::getConnection();
$dir = __DIR__ . '/../migrations';
$files = glob($dir . '/*.sql');
sort($files);
foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        continue;
    }
    $pdo->exec($sql);
}
echo 'Migrações aplicadas';

