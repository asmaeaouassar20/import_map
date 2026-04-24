# algostyle

Importer n'importe quel fichier Excel dans une table MySQL,
avec **mapping visuel des colonnes** pour adapter tout type de fichier.


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

