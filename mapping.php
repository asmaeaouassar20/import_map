<?php
session_start();
if (empty($_SESSION['import'])) {
    header('Location: index.php');
    exit;
}
$imp       = $_SESSION['import'];
$headers   = $imp['headers'];
$preview   = $imp['preview'];
$dbColumns = $imp['db_columns'];
$autoMap   = $imp['auto_map'];
$total     = $imp['total'];
$table     = $imp['table'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mapping — ImportFlow</title>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    body {        
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .card-custom {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        margin-bottom: 1.5rem;
    }
    
    .step-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    
    .automatch-banner {
        background: #d1fae5;
        border-left: 4px solid #10b981;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .automatch-icon {
        background: #10b981;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .mapping-grid {
        background: #f8f9fa;
        border-radius: 0.75rem;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .mapping-header {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr ;
        background: #e9ecef;
        padding: 0.75rem 1rem;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .mapping-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
        transition: background 0.2s;
    }
    
    .mapping-row:hover {
        background: white;
    }
    
    .mapping-row.mapped {
        background: #f0fdf4;
    }
    
    .excel-col {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .col-index {
        background: #1A1A2E;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.875rem;
    }
    
    .col-name {
        font-weight: 500;
    }
    
    .arrow-col {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
   
    
    .mapping-select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }
    
    .sample-value {
        background: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.875rem;
        display: inline-block;
    }
    
    .table-scroll {
        overflow-x: auto;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .preview-table {
        width: 100%;
        font-size: 0.875rem;
    }
    
    .preview-table th {
        background: #f8f9fa;
        padding: 0.75rem;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        background: white;
    }
    
    .preview-table td {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .row-num {
        background: #f8f9fa;
        font-weight: 500;
        color: #6c757d;
    }
    
    .import-confirm {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;        
        bottom: 1rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);     
    }
    
    .confirm-info {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        flex-direction: column;
    }
    
    .confirm-stat{
      display: flex;
      align-items:center;
      gap: 8px;
    }
    
    
    .confirm-num {
        font-size: 1.5rem;
        font-weight: bold;        
    }
    
    .confirm-label {
        display: block;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .mapping-header, .mapping-row {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        
        .mapping-header {
            display: none;
        }
        
        .mapping-row {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: white;
        }
        
        .arrow-col {
            display: none;
        }
        
       
      

    }
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
       
        <a href="index.php" class="btn btn-dark btn-sm">
            <i class="bi bi-arrow-left"></i> Recommencer
        </a>
    </div>

    <!-- Hero Section -->
    <div class="text-center mb-5">                
        <p class="text-dark-50">
            Fichier : <strong><?= htmlspecialchars($imp['filename']) ?></strong> — 
            <strong class="text-info"><?= $total ?> ligne(s)</strong> à importer vers notre table 
            <code class="bg-dark text-white p-1 rounded"><?= htmlspecialchars($table) ?></code>
        </p>
    </div>

    <!-- Mapping Form -->
    <form action="import.php" method="POST" id="mappingForm">
        
        <!-- Mapping Card -->
        <div class="card card-custom">
            <div class="card-body p-4">                
                <h3 class="card-title h4 mb-3">Configuration du mapping</h3>

                <!-- Auto-map badge -->
                <?php $mappedCount = count($autoMap); ?>
                <?php if ($mappedCount > 0): ?>
                <div class="automatch-banner">
                    <span class="automatch-icon">
                        <i class="bi bi-check-lg"></i>
                    </span>
                    <strong><?= $mappedCount ?></strong> colonne(s) mappée(s) automatiquement par correspondance de noms
                </div>
                <?php endif; ?>

                <!-- Mapping Grid -->
                <div class="mapping-grid">
                    <div class="mapping-header">
                        <div>Colonnes de votre fichier Excel</div>                      
                        <div>Colonnes de notre table <code><?= htmlspecialchars($table) ?></code></div>
                        <div>Exemple (1ère ligne)</div>
                    </div>

                    <?php foreach ($headers as $idx => $header): ?>
                    <?php $sample = $preview[0][$idx] ?? '—'; ?>
                    <div class="mapping-row <?= isset($autoMap[$idx]) ? 'mapped' : '' ?>">
                        <div class="excel-col">
                            <span class="col-index"><?=$idx+1 ?></span>
                            <span class="col-name"><?= htmlspecialchars($header ?: '(vide)') ?></span>
                        </div>                        
                        <div>
                            <select name="mapping[<?= $idx ?>]" class="form-select form-select-sm mapping-select">
                                <option value="">— Ignorer —</option>
                                <?php foreach ($dbColumns as $col): ?>
                                <option value="<?= htmlspecialchars($col) ?>"
                                    <?= (isset($autoMap[$idx]) && $autoMap[$idx] === $col) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($col) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <span class="sample-value" title="<?= htmlspecialchars((string)$sample) ?>">                                
                                <?= htmlspecialchars(mb_substr((string)$sample, 0, 30)) ?>
                                <?= mb_strlen((string)$sample) > 30 ? '…' : '' ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="clearAll">
                         Tout effacer
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="autoFill">
                         Re-mapper automatiquement
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="card card-custom">
            <div class="card-body p-4">                
                <h3 class="card-title h4 mb-3">Aperçu des données (5 premières lignes)</h3>
                <div class="table-scroll">
                    <table class="preview-table table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php foreach ($headers as $h): ?>
                                <th><?= htmlspecialchars($h ?: '(col)') ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview as $rowNum => $row): ?>
                            <tr>
                                <td class="row-num"><?= $rowNum + 1 ?></td>
                                <?php foreach ($headers as $idx => $h): ?>
                                <td><?= htmlspecialchars((string)($row[$idx] ?? '')) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Confirmation -->
        <div class="import-confirm  card mt-5">
            <div class="confirm-info">
                <div class="confirm-stat">
                    <div class="confirm-num"><?= $total ?></div>
                    <div class="confirm-label">
                         Lignes à importer
                    </div>
                </div>
                <div class="confirm-stat">
                    <div class="confirm-num" id="mappedCount"><?= $mappedCount ?></div>
                    <div class="confirm-label">
                         Colonnes mappées
                    </div>
                </div>
                <div class="confirm-stat">
                    <div class="confirm-num"><?= count($dbColumns) ?></div>
                    <div class="confirm-label">
                         Colonnes de notre table
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg mt-3" id="importBtn">
                 Lancer l'import
            </button>
        </div>

    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const autoMapData = <?= json_encode($autoMap) ?>;
const dbColumns   = <?= json_encode($dbColumns) ?>;
const headers     = <?= json_encode($headers) ?>;

document.getElementById('clearAll').addEventListener('click', () => {
    document.querySelectorAll('.mapping-select').forEach(s => s.value = '');
    updateMappedCount();
});

document.getElementById('autoFill').addEventListener('click', () => {
    document.querySelectorAll('.mapping-select').forEach((sel, idx) => {
        sel.value = autoMapData[idx] || '';
    });
    updateMappedCount();
});

document.querySelectorAll('.mapping-select').forEach(s => {
    s.addEventListener('change', updateMappedCount);
});

function updateMappedCount() {
    const count = Array.from(document.querySelectorAll('.mapping-select'))
        .filter(s => s.value !== '').length;
    document.getElementById('mappedCount').textContent = count;
}

document.getElementById('mappingForm').addEventListener('submit', function(e) {
    const mapped = Array.from(document.querySelectorAll('.mapping-select'))
        .filter(s => s.value !== '').length;
    if (mapped === 0) {
        e.preventDefault();
        alert('Veuillez mapper au moins une colonne avant d\'importer.');
        return;
    }
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Import en cours…';
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 1s linear infinite;
}
</style>
</body>
</html>