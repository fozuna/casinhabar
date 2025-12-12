<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/src/autoload.php';
$envFile = __DIR__ . '/config/env.php';
if (file_exists($envFile)) {
    require $envFile;
} else {
    require __DIR__ . '/config/env.sample.php';
}

