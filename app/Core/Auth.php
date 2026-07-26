<?php
namespace App\Core;

/**
 * Petit gestionnaire d'authentification basé sur la session PHP.
 * Centralise la connexion/déconnexion et les contrôles d'accès par rôle.
 */
class Auth
{
    /**
     * Démarre la session si ce n'est pas déjà fait.
     * À appeler une seule fois, tout en haut de public/index.php.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Enregistre l'utilisateur connecté en session.
     */
    public static function login(array $utilisateur): void
    {
        $_SESSION['user'] = [
            'id'    => $utilisateur['id'],
            'nom'   => $utilisateur['nom'],
            'prenom'=> $utilisateur['prenom'],
            'email' => $utilisateur['email'],
            'role'  => $utilisateur['role'],
        ];
    }

    /**
     * Déconnecte l'utilisateur courant.
     */
    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Indique si un utilisateur est connecté.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Retourne les infos de l'utilisateur connecté, ou null.
     */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Vérifie que l'utilisateur connecté a bien le rôle attendu.
     */
    public static function hasRole(string $role): bool
    {
        return self::check() && self::user()['role'] === $role;
    }

    /**
     * Bloque l'accès si l'utilisateur n'est pas connecté.
     * À appeler en tête des actions de controller protégées.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Bloque l'accès si l'utilisateur n'a pas le rôle requis.
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (!self::hasRole($role)) {
            header('HTTP/1.0 403 Forbidden');
            die("Accès refusé : réservé au rôle '{$role}'.");
        }
    }
}