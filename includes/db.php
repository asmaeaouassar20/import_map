<?php
require_once 'dbconfig.php';

define('DB_HOST', HOSTNAME);
define('DB_PORT', PORT);
define('DB_NAME', DBNAME);
define('DB_USER', USERNAME);
define('DB_PASS', PASSWORD);
define('DB_CHARSET', 'utf8mb4');


$mysqli = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, DBNAME, PORT);

if (!$mysqli) {
    // code http 500 : erreur serveur
    http_response_code(500);
    header('Content-Type: application/json'); // réponse sera au format JSON
    die(json_encode([
        'error' => 'Connexion DB échouée',
        'details' => mysqli_connect_error()  // détail technique de l'erreur MySQL
    ]));
}

mysqli_set_charset($mysqli, 'utf8mb4');


?>