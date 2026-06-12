<?php
// Copier ce fichier en config.php et remplir les valeurs
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'old_dating');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', '');

function getConnexion(): PDO
{
    $dsn = "pgsql:host=" . DB_HOST
         . ";port="      . DB_PORT
         . ";dbname="    . DB_NAME;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}