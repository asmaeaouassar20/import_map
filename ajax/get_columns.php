<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$table = $_GET['table'] ?? '';
if (empty($table)) {
    echo json_encode([]);
    exit;
}

echo json_encode(getTableColumns($pdo, $table));
