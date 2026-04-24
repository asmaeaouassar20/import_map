<?php
require_once 'dbconfig.php';

define('DB_HOST', HOSTNAME);
define('DB_PORT', PORT);
define('DB_NAME', DBNAME);
define('DB_USER', USERNAME);
define('DB_PASS', PASSWORD);
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // En production, loggez l'erreur sans l'afficher
    die(json_encode(['error' => 'Connexion DB échouée: ' . $e->getMessage()]));
}
