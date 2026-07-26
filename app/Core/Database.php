<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Gère la connexion unique (Singleton) à la base de données PostgreSQL.
 *
 * L'objectif est d'éviter d'ouvrir plusieurs connexions PDO au cours
 * d'une même requête HTTP : tous les Models passent par cette classe
 * pour récupérer LA connexion active.
 */
class Database
{
    /** @var Database|null Unique instance de la classe */
    private static ?Database $instance = null;

    /** @var PDO Connexion PDO active */
    private PDO $conn;

    /**
     * Constructeur privé : empêche l'instanciation directe (new Database()).
     * C'est le cœur du pattern Singleton.
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;options=\'--client_encoding=%s\'',
                $config['host'],
                $config['port'],
                $config['db_name'],
                $config['charset']
            );

            $this->conn = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Connexion à la base de données échouée : ' . $e->getMessage());
        }
    }

    /**
     * Empêche le clonage de l'instance (autre garde-fou du Singleton).
     */
    private function __clone()
    {
    }

    /**
     * Point d'accès global à l'unique instance de Database.
     * Crée l'instance à la première demande (lazy loading).
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion PDO active.
     */
    public function getConnection(): PDO
    {
        return $this->conn;
    }
}