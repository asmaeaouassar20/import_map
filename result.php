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

// Pas besoin de connexion DB ici car tout est en session
// La page reste identique !
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
<link rel="stylesheet" href="assets/style.css" >
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">      
        <a href="index.php" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-circle"></i> Nouvel import
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
        <p class="text-muted">
            <?php if ($isSuccess && !$hasErrors): ?>
                Toutes les données ont été importées avec succès !
            <?php elseif ($isSuccess && $hasErrors): ?>
                Certaines lignes n'ont pas pu être importées. Consultez les détails ci-dessous.
            <?php else: ?>
                L'import a échoué. Vérifiez les erreurs et réessayez.
            <?php endif; ?>
        </p>
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
            <h3 class="card-title h4 mb-3">
                <i class="bi bi-info-circle"></i> Détails de l'opération
            </h3>
            
            <div class="detail-list">
                <div class="detail-row">
                    <span class="detail-key">
                        <i class="bi bi-file-earmark-text"></i> Fichier
                    </span>
                    <span class="detail-val"><?= htmlspecialchars($r['filename']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">
                        <i class="bi bi-table"></i> Table cible
                    </span>
                    <span class="detail-val">
                        <code><?= htmlspecialchars($r['table']) ?></code>
                    </span>
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
                        <?= htmlspecialchars($r['headers'][$idx] ?? "Colonne " . ($idx + 1)) ?>
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
            <i class="bi bi-plus-circle"></i> Nouvel import
        </a>    
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>