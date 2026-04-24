<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$tables = getTableList($pdo);
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ImportFlow — Excel vers Base de Données</title>
<!-- Bootstrap 5 CSS + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    /* Seulement quelques ajustements personnalisés */
    body {        
        min-height: 100vh;
    }
    .title-col{
      color: #1A1A2E;
      font-style: italic;
    }
    .card-custom {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    .file-drop-zone {
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    .text-primary{
        font-size: 20px;
    }
    .file-drop-zone:hover {
        border-color: #0d6efd;
        background: #e9ecef;
    }
    
    .col-chip {
        display: inline-block;
        background: #e9ecef;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        margin: 0.25rem;
    }
    
    .hero-icon {
        font-size: 4rem;
    }
</style>
</head>
<body>

<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-datk">
            <i class="bi bi-database-fill"></i> ImportFlow
        </h1>
        <p class="text-dark-50 lead">Importez vos fichiers Excel vers votre base de données</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Carte principale -->
    <div class="card card-custom">
        <div class="card-body p-4 p-md-5 shadow">
            <h2 class="card-title h4 mb-4">                
                Choisir le fichier & la table cible
            </h2>
            
            <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                <div class="row g-4 ">
                    <!-- Upload Section -->
                    <div class="col-md-6">
                        
                        <div class="file-drop-zone" id="dropZone">
                            <input type="file" name="excel_file" id="fileInput" accept=".xlsx,.xls,.csv" required >                            
                            <p class="mt-2 mb-1">Glissez votre fichier ici</p>
                            <p class="text-muted small">ou <span class="text-primary" style="cursor: pointer;" onclick="document.getElementById('fileInput').click()">cliquez pour parcourir</span></p>
                            <div id="fileName" class="text-success small fw-semibold"></div>
                        </div>
                    </div>

                    <!-- Table Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                             Sélectionnez une table de destination
                        </label>
                        <select name="table_name" id="tableSelect" class="form-select" required>
                            <option value="">— Sélectionner une table —</option>
                            <?php foreach ($tables as $table): ?>
                            <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="tablePreview" class="mt-3"></div>
                    </div>
                </div>

                <!-- Options -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_header" value="1" checked id="hasHeader">
                            <label class="form-check-label" for="hasHeader">
                                 La première ligne contient les en-têtes
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="skip_errors" value="1" id="skipErrors">
                            <label class="form-check-label" for="skipErrors">
                                Ignorer les lignes avec erreurs
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="uploadBtn">
                         Analyser le fichier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Bar -->
    <div class="row mt-4 g-3 shadow">
        
            <div class="alert alert-light mb-0 shadow-sm">
                <i class="bi bi-check-circle-fill text-success"></i>
                Formats supportés : XLSX, XLS, CSV
            </div>
            
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// File input handling
document.getElementById('fileInput').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        document.getElementById('fileName').innerHTML = '<i class="bi bi-file-excel-fill"></i> ' + fileName;
    }
});

// Drag and drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#0d6efd';
    dropZone.style.background = '#e9ecef';
});

dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.background = '#f8f9fa';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        document.getElementById('fileName').innerHTML = '<i class="bi bi-file-excel-fill"></i> ' + files[0].name;
    }
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.background = '#f8f9fa';
});

// Load table columns
document.getElementById('tableSelect').addEventListener('change', function() {
    const table = this.value;
    if (!table) return;
    fetch('ajax/get_columns.php?table=' + encodeURIComponent(table))
        .then(r => r.json())
        .then(cols => {
            const preview = document.getElementById('tablePreview');            
            if (cols.length) {
                preview.innerHTML = '<div> <div class="title-col" >Les colonnes de notre tables sont : </div>' +
                    cols.map(c => `<span class="col-chip">${c}</span>`).join('') +
                    '</div>';
            }
        });
});
</script>
</body>
</html>