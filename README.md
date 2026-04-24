# ImportFlow — Excel vers Base de Données

Outil PHP/JS/CSS pour importer n'importe quel fichier Excel dans une table MySQL,
avec **mapping visuel des colonnes** pour adapter tout type de fichier.

---

## Installation

### 1. Prérequis
- PHP 8.0+
- MySQL / MariaDB
- Composer

### 2. Dépendances PHP
```bash
composer install
```
Cela installera **PhpSpreadsheet** pour lire les fichiers Excel.

### 3. Configuration de la base de données
Ouvrez `includes/db.php` et renseignez vos informations :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 4. Permissions
```bash
chmod 755 uploads/
```

### 5. Serveur web
Placez le dossier dans votre répertoire web (Apache/Nginx) ou utilisez le serveur PHP intégré :
```bash
php -S localhost:8000
```

---

## Utilisation

1. **Ouvrez** `http://localhost:8000/`
2. **Upload** votre fichier Excel (.xlsx, .xls ou .csv)
3. **Choisissez** la table de destination
4. **Mappez** les colonnes de votre fichier vers les colonnes de votre table
   - Le mapping automatique détecte les colonnes avec des noms identiques
   - Vous pouvez mapper manuellement chaque colonne
   - Les colonnes non mappées sont ignorées
5. **Importez** et consultez le rapport

---

## Fonctionnalités

- ✅ Formats supportés : XLSX, XLS, CSV
- ✅ Mapping manuel colonne-par-colonne
- ✅ Auto-mapping par correspondance de noms
- ✅ Aperçu des données avant import
- ✅ Option "ignorer les erreurs" pour import partiel
- ✅ Rapport détaillé (succès, erreurs, durée)
- ✅ Validation sécurité (noms de tables, types de fichiers)
- ✅ Nettoyage automatique des fichiers temporaires

---

## Structure des fichiers

```
excel-import/
├── index.php          # Page principale (upload)
├── upload.php         # Traitement de l'upload
├── mapping.php        # Interface de mapping des colonnes
├── import.php         # Exécution de l'import
├── result.php         # Page de résultat
├── history.php        # Historique (optionnel)
├── composer.json      # Dépendances PHP
├── includes/
│   ├── db.php         # Configuration base de données
│   └── functions.php  # Fonctions utilitaires
├── ajax/
│   └── get_columns.php # API AJAX pour les colonnes
├── assets/
│   ├── style.css      # Styles CSS
│   └── main.js        # JavaScript
└── uploads/           # Fichiers temporaires (auto-nettoyés)
```
