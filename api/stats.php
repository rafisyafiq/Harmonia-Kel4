<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$rentang = (string)($_GET['rentang'] ?? 'all');
$rw = (int)($_GET['rw'] ?? 0);

echo json_encode(public_stats($pdo, $rentang, $rw), JSON_UNESCAPED_UNICODE);
