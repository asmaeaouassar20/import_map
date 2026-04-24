<?php
session_start();
if (empty($_SESSION['import_result'])) {
    header('Location: index.php');
    exit;
}
$r = $_SESSION['import_result'];
unset($_SESSION['import_result']);

$isSuccess = $r['success'] > 0;
$hasErrors = $r['errors'] > 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Résultat — ImportFlow</title>
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
    
    .card-error {
        border-left: 4px solid #dc3545;
    }
    
    .step-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .result-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .result-stat {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        transition: transform 0.2s;
    }
    
    .result-stat:hover {
        transform: translateY(-5px);
    }
    
    .stat-success {
        border: 4px solid #10b981;
    }
    
    .stat-error {
        border: 4px solid #ef4444;
    }
    
    .stat-info {
        border: 4px solid #667eea;
    }
    
    .stat-big {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .stat-success .stat-big {
        color: #10b981;
    }
    
    .stat-error .stat-big {
        color: #ef4444;
    }
    
    .stat-info .stat-big {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .detail-list {
        background: #f8f9fa;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-key {
        font-weight: 600;
        color: #4a5568;
    }
    
    .detail-val {
        color: #1a202c;
        font-family: monospace;
    }
    
    .mapping-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .map-chip {
        background: #f8f9fa;
        border-radius: 2rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .map-chip:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .map-excel {
        font-weight: 600;
        color: #667eea;
    }
    
    .map-arrow {
        color: #6c757d;
    }
    
    .map-db {
        font-family: monospace;
        color: #10b981;
    }
    
    .error-log {
        background: #f8f9fa;
        border-radius: 0.75rem;
        padding: 1rem;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .error-line {
        padding: 0.5rem;
        font-family: monospace;
        font-size: 0.875rem;
        color: #dc3545;
        border-bottom: 1px solid #dee2e6;
    }
    
    .error-line:last-child {
        border-bottom: none;
    }
    
    .error-more {
        color: #6c757d;
        font-style: italic;
    }
    
    .result-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }
    
    .accent {
        background:  #1A1A2E;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }
        
        .result-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .detail-row {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .result-actions {
            flex-direction: column;
        }
        
        .result-actions .btn {
            width: 100%;
        }
    }
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">      
        <a href="index.php" class="btn btn-dark btn-sm">
            Nouvel import
        </a>
    </div>

    <!-- Hero Section -->
    <div class="text-center mb-5">      
        <h2 class="text-dark mb-3">
            <?php if ($isSuccess && !$hasErrors): ?>
                <span class="accent">Succès</span> complet <i class="bi bi-check-circle-fill text-success"></i>
            <?php elseif ($isSuccess && $hasErrors): ?>
                Import <span class="accent">partiel</span> <i class="bi bi-exclamation-triangle-fill text-warning"></i>
            <?php else: ?>
                Import <span class="accent">échoué</span> <i class="bi bi-x-circle-fill text-danger"></i>
            <?php endif; ?>
        </h2>
    </div>

    <!-- Stats -->
    <div class="result-stats">
        <div class="result-stat stat-success">
            <div class="stat-big"><?= number_format($r['success']) ?></div>
            <div class="stat-label">
                <i class="bi bi-check-circle"></i> Lignes insérées
            </div>
        </div>
        <div class="result-stat stat-error">
            <div class="stat-big"><?= number_format($r['errors']) ?></div>
            <div class="stat-label">
                <i class="bi bi-x-circle"></i> Erreurs
            </div>
        </div>
        <div class="result-stat stat-info">
            <div class="stat-big"><?= $r['duration'] ?>s</div>
            <div class="stat-label">
                <i class="bi bi-stopwatch"></i> Durée
            </div>
        </div>
        <div class="result-stat stat-info">
            <div class="stat-big"><?= $r['mapped'] ?></div>
            <div class="stat-label">
                <i class="bi bi-link"></i> Colonnes mappées
            </div>
        </div>
    </div>

    <!-- Détails -->
    <div class="card card-custom">
        <div class="card-body p-4">          
            <h3 class="card-title h4 mb-3">Détails de l'opération</h3>
            
            <div class="detail-list">
                <div class="detail-row">
                    <span class="detail-key">
                        <i class="bi bi-file-earmark-text"></i> Fichier
                    </span>
                    <span class="detail-val"><?= htmlspecialchars($r['filename']) ?></span>
                </div>               
                <div class="detail-row">
                    <span class="detail-key">
                        <i class="bi bi-calendar3"></i> Date / Heure
                    </span>
                    <span class="detail-val"><?= $r['timestamp'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">
                        <i class="bi bi-list-ul"></i> Lignes dans le fichier
                    </span>
                    <span class="detail-val"><?= number_format($r['total']) ?></span>
                </div>
            </div>

            <!-- Mapping utilisé -->
            <h4 class="h6 fw-bold mb-3">
                Mapping utilisé
            </h4>
            <div class="mapping-summary">
                <?php foreach ($r['mapping'] as $idx => $dbCol): ?>
                <div class="map-chip">
                    <span class="map-excel">
                        <i class="bi bi-file-excel"></i> <?= htmlspecialchars($r['headers'][$idx] ?? "Col $idx") ?>
                    </span>
                    <span class="map-arrow">→</span>
                    <span class="map-db">
                        <i class="bi bi-database"></i> <?= htmlspecialchars($dbCol) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Erreurs éventuelles -->
    <?php if (!empty($r['messages'])): ?>
    <div class="card card-custom card-error">
        <div class="card-body p-4">
            <div class="step-badge" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 class="card-title h4 mb-3">
                Messages d'erreur <span class="badge bg-danger"><?= count($r['messages']) ?></span>
            </h3>
            <div class="error-log">
                <?php foreach (array_slice($r['messages'], 0, 50) as $msg): ?>
                <div class="error-line">
                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endforeach; ?>
                <?php if (count($r['messages']) > 50): ?>
                <div class="error-line error-more">
                    <i class="bi bi-three-dots"></i> … et <?= count($r['messages']) - 50 ?> autres erreurs
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="result-actions">
        <a href="index.php" class="btn btn-primary btn-lg">
          Nouvel import
        </a>       
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>