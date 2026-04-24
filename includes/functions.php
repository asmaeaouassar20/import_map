<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Retourne la liste des tables de la base de données
 */
function getTableList(PDO $pdo): array {
    $myTables=['vehicules'];
    return $myTables;
}

/**
 * Retourne les colonnes d'une table donnée
 */
function getTableColumns(PDO $pdo, string $table): array {
    // Valider le nom de table (caractères alphanumériques et underscores uniquement)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }
    $stmt = $pdo->query("DESCRIBE `$table`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_column($cols, 'Field');
}

/**
 * Retourne les métadonnées complètes des colonnes d'une table
 */
function getTableColumnsMeta(PDO $pdo, string $table): array {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }
    $stmt = $pdo->query("DESCRIBE `$table`");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lit un fichier Excel/CSV et retourne les en-têtes + premières lignes
 */
function readExcelPreview(string $filePath, bool $hasHeader = true): array {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        return readCsvPreview($filePath, $hasHeader);
    }

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false);

    if (empty($rows)) return ['headers' => [], 'preview' => [], 'total' => 0];

    $total = count($rows);
    $headers = $hasHeader ? array_map('trim', $rows[0]) : range(1, count($rows[0]));
    $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;
    $preview = array_slice($dataRows, 0, 5);

    return [
        'headers' => $headers,
        'preview' => $preview,
        'total'   => $hasHeader ? $total - 1 : $total,
    ];
}

/**
 * Lit un CSV
 */
function readCsvPreview(string $filePath, bool $hasHeader = true): array {
    $rows = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
    }

    if (empty($rows)) return ['headers' => [], 'preview' => [], 'total' => 0];

    $total = count($rows);
    $headers = $hasHeader ? array_map('trim', $rows[0]) : range(1, count($rows[0]));
    $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;
    $preview = array_slice($dataRows, 0, 5);

    return [
        'headers' => $headers,
        'preview' => $preview,
        'total'   => $hasHeader ? $total - 1 : $total,
    ];
}

/**
 * Lit TOUTES les lignes d'un fichier Excel/CSV
 */
function readAllRows(string $filePath, bool $hasHeader = true): array {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
    } else {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    return $hasHeader ? array_slice($rows, 1) : $rows;
}

/**
 * Tente un mapping automatique entre colonnes Excel et colonnes DB
 * (correspondance par similarité de noms, insensible à la casse)
 */
function autoMapColumns(array $excelHeaders, array $dbColumns): array {
    $mapping = [];
    foreach ($excelHeaders as $idx => $header) {
        $normalizedHeader = strtolower(trim($header));
        foreach ($dbColumns as $col) {
            if (strtolower($col) === $normalizedHeader) {
                $mapping[$idx] = $col;
                break;
            }
        }
    }
    return $mapping;
}

/**
 * Importe les données dans la table avec le mapping fourni
 * Retourne un tableau avec le nombre de succès/erreurs et les messages
 */
function importData(PDO $pdo, string $table, string $filePath, array $mapping, bool $hasHeader, bool $skipErrors): array {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return ['success' => 0, 'errors' => 0, 'messages' => ['Nom de table invalide']];
    }

    $rows = readAllRows($filePath, $hasHeader);
    $success = 0;
    $errors  = 0;
    $messages = [];

    // Préparer la requête d'insertion
    $dbCols  = array_values($mapping);
    $colList = implode(', ', array_map(fn($c) => "`$c`", $dbCols));
    $placeholders = implode(', ', array_fill(0, count($dbCols), '?'));
    $sql = "INSERT INTO `$table` ($colList) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);

    $pdo->beginTransaction();
    try {
        foreach ($rows as $rowNum => $row) {
            $values = [];
            foreach (array_keys($mapping) as $colIdx) {
                $values[] = isset($row[$colIdx]) ? (trim($row[$colIdx]) === '' ? null : trim($row[$colIdx])) : null;
            }
            try {
                $stmt->execute($values);
                $success++;
            } catch (PDOException $e) {
                $errors++;
                $messages[] = "Ligne " . ($rowNum + 2) . " : " . $e->getMessage();
                if (!$skipErrors) {
                    $pdo->rollBack();
                    return ['success' => 0, 'errors' => $errors, 'messages' => $messages];
                }
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => 0, 'errors' => 1, 'messages' => [$e->getMessage()]];
    }

    return ['success' => $success, 'errors' => $errors, 'messages' => $messages];
}

/**
 * Nettoie les anciens fichiers uploadés (> 1h)
 */
function cleanupOldFiles(string $dir): void {
    foreach (glob($dir . '/*') as $file) {
        if (is_file($file) && (time() - filemtime($file)) > 3600) {
            unlink($file);
        }
    }
}
