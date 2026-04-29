<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


/**
 * Retourne la liste des tables de la base de données
 * @param mysqli $mysqli La connexion MySQLi
 * @return array Liste des noms des tables
 */
function getTableList(mysqli $mysqli): array {
    $tables = [];
    
    // Requête pour obtenir les tables de la base courante
    $result = mysqli_query($mysqli, "SHOW TABLES");
    
    // Vérifier si la requête a réussi
    if (!$result) {
        // En cas d'erreur, retourner un tableau vide ou lever une exception
        error_log("Erreur getTableList: " . mysqli_error($mysqli));
        return $tables;
    }
    
    // Parcourir les résultats
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $tables[] = $row[0]; // Le nom de la table est dans la première colonne
    }
    
    // Libérer le résultat
    mysqli_free_result($result);
    
    return $tables;
}

/**
 * Retourne les colonnes d'une table donnée
 */
function getTableColumns(mysqli $mysqli, string $table): array {
    // Valider le nom de table (caractères alphanumériques et underscores uniquement)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }
    
    $result = mysqli_query($mysqli, "DESCRIBE `$table`");
    if (!$result) {
        error_log("Erreur getTableColumns: " . mysqli_error($mysqli));
        return [];
    }
    
    $cols = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cols[] = $row['Field'];
    }
    
    mysqli_free_result($result);
    return $cols;
}

/**
 * Retourne les métadonnées complètes des colonnes d'une table
 */
function getTableColumnsMeta(mysqli $mysqli, string $table): array {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }
    
    $result = mysqli_query($mysqli, "DESCRIBE `$table`");
    if (!$result) {
        error_log("Erreur getTableColumnsMeta: " . mysqli_error($mysqli));
        return [];
    }
    
    $meta = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $meta[] = $row;
    }
    
    mysqli_free_result($result);
    return $meta;
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
    $preview = $dataRows;

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
 * Mapping automatique intelligent des colonnes entre un fichier Excel et une table MySQL.
 * 
 * Cette fonction tente de faire correspondre automatiquement les colonnes du fichier Excel
 * avec les colonnes de la base de données, même en cas de différences mineures :
 * - accents (marque vs mark)
 * - majuscules/minuscules
 * - caractères spéciaux, underscores, espaces
 * - fautes de frappe légères ou abréviations
 * 
 * Exemples de rapprochements possibles :
 *   "mark"      → "marque"
 *   "Prix TTC"  → "prix"
 *   "nom_client"→ "client_nom"
 *   "quantite"  → "qty"
 *
 * @param array $excelHeaders  Liste des en-têtes du fichier Excel
 * @param array $dbColumns     Liste des colonnes de la table MySQL
 * @return array               Tableau associatif [index_col_excel => nom_colonne_db]
 */
function autoMapColumns(array $excelHeaders, array $dbColumns): array
{
    $mapping = [];  // mapping final => [index_colonne_excel => nom_colonne_db]  :  exemple : $mapping = [0 => 'client_nom', 1 => 'prix', 2 => 'qty' ];
    $usedDbCols = [];        // Permet d'éviter qu'une même colonne DB soit mappée plusieurs fois

    /**
     * Fonction de normalisation pour rendre les comparaisons plus robustes.
     * Elle transforme une chaîne en une forme simplifiée :
     * - Minuscules
     * - Suppression des accents
     * - Suppression de tous les caractères non alphanumériques
     */
    $normalize = function(string $str): string {
        // Conversion en minuscules avec support UTF-8
        $str = mb_strtolower($str, 'UTF-8');
        
        // Suppression des accents (ex: é → e, ç → c)
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        
        // Suppression de tout ce qui n'est pas lettre ou chiffre
        // (espaces, underscores, tirets, points, virgules, etc.)
        $str = preg_replace('/[^a-z0-9]/u', '', $str);
        
        return $str;
    };

    // Pré-calcul de la version normalisée de toutes les colonnes de la base de données
    // pour éviter de recalculer à chaque itération
    $normalizedDb = [];
    foreach ($dbColumns as $col) {
        $normalizedDb[$col] = $normalize($col);
    }

    // Parcours de chaque colonne du fichier Excel
    foreach ($excelHeaders as $idx => $header) {
        
        // On ignore les colonnes vides
        if (empty(trim($header))) {
            continue;
        }

        $normHeader = $normalize($header);   // Version normalisée de l'en-tête Excel
        $bestMatch  = null;                  // Meilleure correspondance trouvée
        $bestScore  = 0;                     // Score de similarité le plus élevé

        // On compare avec chaque colonne de la table MySQL
        foreach ($dbColumns as $dbCol) {
            
            // Si cette colonne DB est déjà mappée à une autre colonne Excel → on passe
            if (in_array($dbCol, $usedDbCols)) {
                continue;
            }

            $normDb = $normalizedDb[$dbCol];

            // === 1. Correspondance EXACTE après normalisation (meilleur cas) ===
            if ($normHeader === $normDb) {
                $bestMatch = $dbCol;
                $bestScore = 100;
                break;  // car on n'a pas besoin de chercher plus loin
            }

            // === 2. Calcul de similarité avec similar_text() ===
            // Retourne un pourcentage de similarité entre 0 et 100
            similar_text($normHeader, $normDb, $percent);

            // === 3. Bonus de similarité si un mot est contenu dans l'autre ===
            // Exemple : "nomclient" contient "nom" → on booste le score
            if (str_contains($normHeader, $normDb) || str_contains($normDb, $normHeader)) {
                $percent = max($percent, 85);
            }

            // On garde la meilleure correspondance si elle dépasse le seuil minimum
            if ($percent > $bestScore && $percent >= 70) {   // Seuil recommandé : 70%
                $bestScore = $percent;
                $bestMatch = $dbCol;
            }
        }

        // Si on a trouvé une correspondance acceptable, on l'enregistre
        if ($bestMatch !== null) {
            $mapping[$idx] = $bestMatch;
            $usedDbCols[] = $bestMatch;   // Marquer cette colonne DB comme utilisée
        }
    }

    return $mapping;
}

/**
 * Importe les données dans la table avec le mapping fourni
 * Retourne un tableau avec le nombre de succès/erreurs et les messages
 * Version MySQLi avec requêtes préparées
 */
function importData(mysqli $mysqli, string $table, string $filePath, array $mapping, bool $hasHeader, bool $skipErrors): array {
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
    
    $stmt = mysqli_prepare($mysqli, $sql);
    if (!$stmt) {
        return ['success' => 0, 'errors' => 1, 'messages' => ['Erreur de préparation: ' . mysqli_error($mysqli)]];
    }

    // Déterminer les types pour bind_param
    $types = str_repeat('s', count($dbCols));
    
    mysqli_begin_transaction($mysqli);
    
    try {
        foreach ($rows as $rowNum => $row) {
            $values = [];
            foreach (array_keys($mapping) as $colIdx) {
                $value = isset($row[$colIdx]) ? (trim($row[$colIdx]) === '' ? null : trim($row[$colIdx])) : null;
                $values[] = $value;
            }
            
            // bind_param avec références
            $bindParams = [$types];
            foreach ($values as $key => &$value) {
                $bindParams[] = &$value;
            }
            
            if (!call_user_func_array([$stmt, 'bind_param'], $bindParams)) {
                throw new Exception("Erreur bind_param");
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success++;
            } else {
                $errors++;
                $messages[] = "Ligne " . ($rowNum + 2) . " : " . mysqli_stmt_error($stmt);
                if (!$skipErrors) {
                    mysqli_rollback($mysqli);
                    mysqli_stmt_close($stmt);
                    return ['success' => 0, 'errors' => $errors, 'messages' => $messages];
                }
            }
        }
        mysqli_commit($mysqli);
    } catch (Exception $e) {
        mysqli_rollback($mysqli);
        mysqli_stmt_close($stmt);
        return ['success' => 0, 'errors' => 1, 'messages' => [$e->getMessage()]];
    }
    
    mysqli_stmt_close($stmt);
    return ['success' => $success, 'errors' => $errors, 'messages' => $messages];
}

/**
 * Version alternative d'importData avec requête préparée plus simple
 * Utilise une nouvelle requête pour chaque ligne (moins efficace mais plus fiable)
 */
function importDataSimple(mysqli $mysqli, string $table, string $filePath, array $mapping, bool $hasHeader, bool $skipErrors): array {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return ['success' => 0, 'errors' => 0, 'messages' => ['Nom de table invalide']];
    }

    $rows = readAllRows($filePath, $hasHeader);
    $success = 0;
    $errors  = 0;
    $messages = [];
    $dbCols = array_values($mapping);

    mysqli_begin_transaction($mysqli);
    
    try {
        foreach ($rows as $rowNum => $row) {
            // Préparer les valeurs
            $values = [];
            $types = '';
            foreach (array_keys($mapping) as $colIdx) {
                $value = isset($row[$colIdx]) ? (trim($row[$colIdx]) === '' ? null : trim($row[$colIdx])) : null;
                $values[] = $value;
                $types .= 's'; // tous considérés comme string
            }
            
            // Construire et exécuter la requête
            $colList = implode(', ', array_map(fn($c) => "`$c`", $dbCols));
            $placeholders = implode(', ', array_fill(0, count($dbCols), '?'));
            $sql = "INSERT INTO `$table` ($colList) VALUES ($placeholders)";
            
            $stmt = mysqli_prepare($mysqli, $sql);
            if (!$stmt) {
                throw new Exception("Erreur préparation: " . mysqli_error($mysqli));
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$values);
            
            if (mysqli_stmt_execute($stmt)) {
                $success++;
            } else {
                $errors++;
                $messages[] = "Ligne " . ($rowNum + 2) . " : " . mysqli_stmt_error($stmt);
                if (!$skipErrors) {
                    mysqli_rollback($mysqli);
                    mysqli_stmt_close($stmt);
                    return ['success' => 0, 'errors' => $errors, 'messages' => $messages];
                }
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_commit($mysqli);
    } catch (Exception $e) {
        mysqli_rollback($mysqli);
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