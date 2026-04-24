<?php
session_start();
require_once 'includes/db.php';  
require_once 'includes/functions.php';  

$tables = getTableList($mysqli);  
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
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .card-custom {
        border-radius: 1.5rem;
        border: none;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
    }
    
    .card-custom .shadow {
        border-radius: 1.5rem;
    }
    
    .file-drop-zone {
        border: 2px dashed #dee2e6;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    
    .file-drop-zone:hover {
        border-color: #667eea;
        background: #f0f0ff;
        transform: translateY(-2px);
    }
    
    .file-drop-zone.dragover {
        border-color: #667eea;
        background: #e8e8ff;
    }
    
    .btn-primary {
        background: var(--primary-gradient);
        border: none;
        border-radius: 2rem;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }
    
    .col-chip {
        display: inline-block;
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        color: #667eea;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        margin: 0.25rem;
        font-family: monospace;
        transition: all 0.2s;
    }
    
    .col-chip:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: scale(1.05);
    }
    
    .title-col {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    
    .display-4 {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .form-select, .form-control {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    .alert {
        border-radius: 1rem;
        border: none;
    }
    
    .info-bar {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem !important;
        }
        
        .display-4 {
            font-size: 2rem;
        }
    }
</style>
</head>
<body>

<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">
            ImportFlow
        </h1>
        <p class="text-muted lead">Importez vos fichiers Excel vers votre base de données</p>
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
        <div class="card-body p-4 p-md-5">
            <h2 class="card-title h4 mb-4">
                <i class="bi bi-cloud-upload"></i> Choisir le fichier & la table cible
            </h2>
            
            <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Upload Section -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-file-earmark-excel"></i> Fichier à importer
                        </label>
                        <div class="file-drop-zone" id="dropZone">
                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #667eea;"></i>
                            <input type="file" name="excel_file" id="fileInput" accept=".xlsx,.xls,.csv" required style="display: none;">
                            <p class="mt-2 mb-1">Glissez votre fichier ici</p>
                            <p class="text-muted small">ou <span class="text-primary" style="cursor: pointer;" onclick="document.getElementById('fileInput').click()">cliquez pour parcourir</span></p>
                            <div id="fileName" class="text-success small fw-semibold"></div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Formats supportés : XLSX, XLS, CSV (max 10 Mo)
                            </small>
                        </div>
                    </div>

                    <!-- Table Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-table"></i> Sélectionnez une table de destination
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
                                <i class="bi bi-text-paragraph"></i> La première ligne contient les en-têtes
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="skip_errors" value="1" id="skipErrors">
                            <label class="form-check-label" for="skipErrors">
                                <i class="bi bi-skip-forward"></i> Ignorer les lignes avec erreurs
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="uploadBtn">
                        <i class="bi bi-arrow-right-circle"></i> Analyser le fichier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Bar -->
    <div class="row mt-4 g-3">
        <div class="col-12">
            <div class="info-bar shadow-sm">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-center">
                    <span><i class="bi bi-check-circle-fill text-success"></i> Formats supportés : XLSX, XLS, CSV</span>
                    <span><i class="bi bi-database"></i> Import sécurisé</span>
                    <span><i class="bi bi-shield-check"></i> Requêtes préparées</span>
                </div>
            </div>
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
        document.getElementById('dropZone').style.borderColor = '#10b981';
    }
});

// Drag and drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('click', () => {
    fileInput.click();
});

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        const fileName = files[0].name;
        document.getElementById('fileName').innerHTML = '<i class="bi bi-file-excel-fill"></i> ' + fileName;
        dropZone.style.borderColor = '#10b981';
    }
});

// Load table columns via AJAX (à créer)
document.getElementById('tableSelect').addEventListener('change', function() {
    const table = this.value;
    const previewDiv = document.getElementById('tablePreview');
    
    if (!table) {
        previewDiv.innerHTML = '';
        return;
    }
    
    // Afficher un indicateur de chargement
    previewDiv.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Chargement des colonnes...</div>';
    
    fetch('ajax/get_columns.php?table=' + encodeURIComponent(table))
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(cols => {
            if (cols.length && !cols.error) {
                previewDiv.innerHTML = `
                    <div class="p-3 bg-light rounded-3">
                        <div class="title-col">
                            <i class="bi bi-columns"></i> Colonnes de la table <code>${escapeHtml(table)}</code> :
                        </div>
                        <div>
                            ${cols.map(c => `<span class="col-chip">${escapeHtml(c)}</span>`).join('')}
                        </div>
                    </div>
                `;
            } else if (cols.error) {
                previewDiv.innerHTML = `<div class="alert alert-warning small">${escapeHtml(cols.error)}</div>`;
            } else {
                previewDiv.innerHTML = '<div class="alert alert-info small">Aucune colonne trouvée</div>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            previewDiv.innerHTML = '<div class="alert alert-danger small">Erreur lors du chargement des colonnes</div>';
        });
});

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Validation du formulaire avant soumission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const file = fileInput.files[0];
    const table = document.getElementById('tableSelect').value;
    
    if (!file) {
        e.preventDefault();
        alert('Veuillez sélectionner un fichier');
        return;
    }
    
    if (!table) {
        e.preventDefault();
        alert('Veuillez sélectionner une table cible');
        return;
    }
    
    // Vérifier la taille du fichier (10 Mo)
    if (file.size > 10 * 1024 * 1024) {
        e.preventDefault();
        alert('Le fichier ne doit pas dépasser 10 Mo');
        return;
    }
    
    // Afficher un indicateur de chargement
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split spin"></i> Analyse en cours...';
});

// Style pour l'animation de chargement
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin {
        display: inline-block;
        animation: spin 1s linear infinite;
    }
    .dragover {
        border-color: #667eea !important;
        background: #f0f0ff !important;
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>