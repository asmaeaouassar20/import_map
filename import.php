<?php
session_start();
require_once 'includes/db.php'; // Nouveau fichier avec connexion MySQLi
require_once 'includes/functions.php'; // Vos fonctions converties

if (empty($_SESSION['import']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$imp        = $_SESSION['import'];
$rawMapping = $_POST['mapping'] ?? [];

// Nettoyer le mapping : ne garder que les colonnes avec une valeur DB
$cleanMapping = [];
foreach ($rawMapping as $idx => $dbCol) {
    if (!empty($dbCol)) {
        $cleanMapping[(int)$idx] = $dbCol;
    }
}

if (empty($cleanMapping)) {
    $_SESSION['error'] = 'Veuillez mapper au moins une colonne.';
    header('Location: mapping.php');
    exit;
}

// Vérifier que le fichier existe encore
if (!file_exists($imp['filepath'])) {
    $_SESSION['error'] = 'Le fichier temporaire a expiré. Veuillez recommencer.';
    unset($_SESSION['import']);
    header('Location: index.php');
    exit;
}

// Vérifier que la connexion MySQLi existe
if (!isset($mysqli) || !$mysqli) {
    $_SESSION['error'] = 'Erreur de connexion à la base de données.';
    header('Location: index.php');
    exit;
}

// Lancer l'import
$startTime = microtime(true);
$result = importData(
    $mysqli,        // Changement: $pdo → $mysqli
    $imp['table'],
    $imp['filepath'],
    $cleanMapping,
    $imp['has_header'],
    $imp['skip_errors']
);
$duration = round(microtime(true) - $startTime, 2);

// Nettoyer le fichier temporaire
@unlink($imp['filepath']);

// Stocker le résultat
$_SESSION['import_result'] = [
    'success'   => $result['success'],
    'errors'    => $result['errors'],
    'messages'  => $result['messages'],
    'duration'  => $duration,
    'table'     => $imp['table'],
    'filename'  => $imp['filename'],
    'total'     => $imp['total'],
    'mapped'    => count($cleanMapping),
    'mapping'   => $cleanMapping,
    'headers'   => $imp['headers'],
    'timestamp' => date('d/m/Y H:i:s'),
];

unset($_SESSION['import']);
header('Location: result.php');
exit;