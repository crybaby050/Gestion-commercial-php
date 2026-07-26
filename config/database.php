<?php
/**
 * Configuration de connexion à la base de données.
 * Les valeurs sont lues depuis le fichier .env (chargé dans public/index.php).
 */
return [
    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
    'port'     => $_ENV['DB_PORT'] ?? '5432',
    'db_name'  => $_ENV['DB_NAME'] ?? 'gestion_commerciale',
    'username' => $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8',
];