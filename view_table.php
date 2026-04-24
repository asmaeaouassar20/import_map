<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Récupérer le nom de la table
$table = $_GET['table'] ?? '';

// Valider le nom de la table
if (empty($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
    $_SESSION['error'] = 'Table invalide ou non spécifiée.';
    header('Location: index.php');
    exit;
}

// Vérifier que la table existe
$tables = getTableList($mysqli);
if (!in_array($table, $tables)) {
    $_SESSION['error'] = "La table '$table' n'existe pas dans la base de données.";
    header('Location: index.php');
    exit;
}

// Récupérer les colonnes de la table
$columns = getTableColumnsMeta($mysqli, $table);

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Compter le nombre total d'enregistrements
$countResult = mysqli_query($mysqli, "SELECT COUNT(*) as total FROM `$table`");
$totalRows = 0;
if ($countResult) {
    $row = mysqli_fetch_assoc($countResult);
    $totalRows = $row['total'];
    mysqli_free_result($countResult);
}
$totalPages = ceil($totalRows / $perPage);

// Récupérer les données de la table
$query = "SELECT * FROM `$table` LIMIT $offset, $perPage";
$result = mysqli_query($mysqli, $query);

// Vérifier si la requête a réussi
if (!$result) {
    die("Erreur lors de la récupération des données : " . mysqli_error($mysqli));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table <?= htmlspecialchars($table) ?> — ImportFlow</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .card-custom {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .table-responsive {
            max-height: 70vh;
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 1rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .badge-null {
            background: #e9ecef;
            color: #6c757d;
            font-family: monospace;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            transition: transform 0.2s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-outline-gradient {
            border: 1px solid #667eea;
            color: #667eea;
            border-radius: 2rem;
            transition: all 0.2s;
        }
        
        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .pagination .page-link {
            color: #667eea;
            border-radius: 0.5rem;
            margin: 0 0.25rem;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }
        
        .info-toolbar {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .col-chip {
            display: inline-block;
            background: #f0f0ff;
            color: #667eea;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            margin: 0.125rem;
            font-family: monospace;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .table td, .table th {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="index.php" class="btn btn-gradient">
            <i class="bi bi-arrow-left"></i> Nouvel import
        </a>
        <h2 class="h4 mb-0">
            <i class="bi bi-table"></i> Table : 
            <code class="bg-dark text-white p-2 rounded"><?= htmlspecialchars($table) ?></code>
        </h2>
        <button onclick="window.print()" class="btn btn-outline-gradient">
            <i class="bi bi-printer"></i> Imprimer
        </button>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-table" style="font-size: 2rem; color: #667eea;"></i>
                <div class="stat-number"><?= count($columns) ?></div>
                <small class="text-muted">Colonnes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-database" style="font-size: 2rem; color: #667eea;"></i>
                <div class="stat-number"><?= number_format($totalRows) ?></div>
                <small class="text-muted">Enregistrements</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-files" style="font-size: 2rem; color: #667eea;"></i>
                <div class="stat-number"><?= number_format($totalPages) ?></div>
                <small class="text-muted">Pages (<?= $perPage ?> par page)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-clock-history" style="font-size: 2rem; color: #667eea;"></i>
                <div class="stat-number">
                    <?php
                    // Obtenir la date de dernière modification (optionnel)
                    $lastUpdate = mysqli_query($mysqli, "SELECT MAX(id_vehicule) as last FROM `$table`");
                    ?>
                </div>
                <small class="text-muted">Dernier import</small>
            </div>
        </div>
    </div>

    <!-- Structure de la table -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="bi bi-info-circle"></i> Structure de la table
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($columns as $col): ?>
                <span class="col-chip">
                    <i class="bi bi-columns"></i> <?= htmlspecialchars($col['Field']) ?>
                    <small class="text-muted">(<?= $col['Type'] ?>)</small>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Données de la table -->
    <div class="card card-custom">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul"></i> Contenu de la table
                </h5>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width: 200px;">
                    <button class="btn btn-sm btn-outline-gradient" onclick="exportToCSV()">
                        <i class="bi bi-download"></i> Exporter CSV
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                        <th><?= htmlspecialchars($col['Field']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <?php 
                                $value = $row[$col['Field']];
                                $displayValue = $value;
                                $isNull = ($value === null);
                                ?>
                            <td title="<?= htmlspecialchars($displayValue ?? 'NULL') ?>">
                                <?php if ($isNull): ?>
                                    <span class="badge badge-null">NULL</span>
                                <?php else: ?>
                                    <?= htmlspecialchars(mb_substr((string)$displayValue, 0, 50)) ?>
                                    <?= mb_strlen((string)$displayValue) > 50 ? '…' : '' ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($columns) ?>" class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2">Aucune donnée dans cette table</p>
                                <a href="index.php" class="btn btn-gradient btn-sm">
                                    <i class="bi bi-cloud-upload"></i> Importer des données
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <!-- Première page -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?table=<?= urlencode($table) ?>&page=1">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Précédent -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $page - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <!-- Pages numérotées -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;
                    
                    if ($endPage < $totalPages): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    
                    <!-- Suivant -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $page + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <!-- Dernière page -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?table=<?= urlencode($table) ?>&page=<?= $totalPages ?>">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="text-center mt-2 text-muted small">
                Page <?= $page ?> sur <?= $totalPages ?> - Total : <?= number_format($totalRows) ?> enregistrements
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Recherche dans le tableau
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('dataTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        let rowText = '';
        const cells = row.getElementsByTagName('td');
        
        for (let j = 0; j < cells.length; j++) {
            rowText += cells[j].innerText.toLowerCase() + ' ';
        }
        
        if (rowText.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
});

// Export CSV
function exportToCSV() {
    const table = document.getElementById('dataTable');
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    // Récupérer les en-têtes
    const headers = [];
    const headerCells = rows[0].querySelectorAll('th');
    headerCells.forEach(cell => headers.push(cell.innerText));
    csv.push(headers.join(','));
    
    // Récupérer les données
    const dataRows = table.querySelectorAll('tbody tr');
    dataRows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            const rowData = [];
            cells.forEach(cell => {
                let text = cell.innerText;
                // Échapper les guillemets
                text = text.replace(/"/g, '""');
                // Mettre entre guillemets si contient des virgules
                if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                    text = '"' + text + '"';
                }
                rowData.push(text);
            });
            csv.push(rowData.join(','));
        }
    });
    
    // Télécharger le fichier
    const blob = new Blob(["\uFEFF" + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', '<?= htmlspecialchars($table) ?>_export.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

</body>
</html>