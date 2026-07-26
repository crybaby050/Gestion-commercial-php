<?php
namespace App\Core;

/**
 * Classe de base pour tous les Controllers.
 * Fournit le rendu des vues (avec injection dans le layout commun)
 * et un helper de redirection.
 */
class Controller
{
    /**
     * Rend une vue en l'injectant dans le layout commun (views/layout/base.php).
     *
     * @param string $view Chemin de la vue relatif à /views, sans extension (ex: 'client/index')
     * @param array  $data Données à extraire comme variables dans la vue
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("Vue '{$view}' introuvable.");
        }

        // On capture le rendu de la vue dans $content, puis on l'injecte
        // dans le layout commun qui contient le HTML partagé (header, sidebar...).
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = __DIR__ . '/../../views/layout/base.php';
        require $layoutFile;
    }

    /**
     * Rend une vue SANS layout (utile pour la page de login, par exemple).
     */
    protected function viewOnly(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("Vue '{$view}' introuvable.");
        }
        require $viewFile;
    }

    /**
     * Redirige vers une URL relative à la racine de l'application.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . $url);
        exit;
    }
}