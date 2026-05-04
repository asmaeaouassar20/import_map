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

### 3. dbconfig.php
Créer un fichier dbconfig.php dans le dossier includes. dbconfig.php contenant vos identifiants

### 4. Configuration de la base de données
Ouvrez `includes/db.php` et renseignez vos informations :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```



### 5. Screenshots
<img width="1497" height="836" alt="image" src="https://github.com/user-attachments/assets/29ae0cbf-4dc1-4896-b024-67f54c01fc7c" />
<br>
<hr>
<img width="1507" height="539" alt="hey" src="https://github.com/user-attachments/assets/671fdebd-eac3-47bb-8246-e42d270144ca" />
<br>
<hr>
<img width="1285" height="290" alt="image" src="https://github.com/user-attachments/assets/d23fcd30-d3d7-4979-b8b4-f1c753c4b09e" />
<br>
<hr>
<img width="1342" height="378" alt="image" src="https://github.com/user-attachments/assets/9d949d9e-1333-49a8-8a01-6b0fcadab3e3" />
<br>
<hr>

