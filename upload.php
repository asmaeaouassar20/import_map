<?php
session_start();
require_once 'includes/db.php';  
require_once 'includes/functions.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$tableName = $_POST['table_name'] ?? '';
$hasHeader = isset($_POST['has_header']);
$skipErrors = isset($_POST['skip_errors']);

// Validation
if (empty($tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    $_SESSION['error'] = 'Veuillez sélectionner une table valide.';
    header('Location: index.php');
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Erreur lors de l\'upload du fichier.';
    header('Location: index.php');
    exit;
}

$file = $_FILES['excel_file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    $_SESSION['error'] = 'Format non supporté. Utilisez XLSX, XLS ou CSV.';
    header('Location: index.php');
    exit;
}

// Taille max : 10 Mo
if ($file['size'] > 10 * 1024 * 1024) {
    $_SESSION['error'] = 'Le fichier dépasse 10 Mo.';
    header('Location: index.php');
    exit;
}

// Créer le dossier uploads si nécessaire
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

cleanupOldFiles($uploadDir);

// Sauvegarder le fichier
$filename = uniqid('import_', true) . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    $_SESSION['error'] = 'Impossible de sauvegarder le fichier.';
    header('Location: index.php');
    exit;
}

// Lire les en-têtes et aperçu
try {
    $data = readExcelPreview($filepath, $hasHeader);
} catch (Exception $e) {
    unlink($filepath);
    $_SESSION['error'] = 'Impossible de lire le fichier : ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

if (empty($data['headers'])) {
    unlink($filepath);
    $_SESSION['error'] = 'Le fichier semble vide ou illisible.';
    header('Location: index.php');
    exit;
}

// Récupérer les colonnes de la table cible avec MySQLi
$dbColumns = getTableColumns($mysqli, $tableName);  // Changé : $pdo → $mysqli
$autoMap   = autoMapColumns($data['headers'], $dbColumns);

// Stocker en session pour la page de mapping
$_SESSION['import'] = [
    'filepath'    => $filepath,
    'filename'    => $file['name'],
    'table'       => $tableName,
    'has_header'  => $hasHeader,
    'skip_errors' => $skipErrors,
    'headers'     => $data['headers'],
    'preview'     => $data['preview'],
    'total'       => $data['total'],
    'db_columns'  => $dbColumns,
    'auto_map'    => $autoMap,
];

header('Location: mapping.php');
exit;
?>