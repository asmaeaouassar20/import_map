<?php
// ajax/get_columns.php
require_once '../includes/db.php';  
require_once '../includes/functions.php';  

header('Content-Type: application/json');

$table = $_GET['table'] ?? '';
if (empty($table)) {
    echo json_encode(['error' => 'Aucune table spécifiée']);
    exit;
}

// Valider le nom de la table
if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
    echo json_encode(['error' => 'Nom de table invalide']);
    exit;
}

// Récupérer les colonnes avec MySQLi
$columns = getTableColumns($mysqli, $table);

if (empty($columns)) {
    echo json_encode(['error' => 'Aucune colonne trouvée pour cette table']);
} else {
    echo json_encode($columns);
}
?>