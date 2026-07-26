<?php
/**
 * Point d'entrée unique de l'application (front controller).
 */

// Autoloader manuel (fonctionne même sans `composer install`)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Auth;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

Auth::start();

// Détermination de BASE_URL (compatible sous-dossier, ex: /gcd/public/)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
define('BASE_URL', $baseDir);

// Analyse de l'URL demandée
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$url = '/' . trim(substr($path, strlen($baseDir)), '/');
if ($url === '') {
    $url = '/';
}

// AJOUT : rediriger la racine vers la page de connexion
if ($url === '/') {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routeKey = $method . ' ' . $url;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routeKey = $method . ' ' . $url;

$routes = require __DIR__ . '/../routes/web.php';

if (!array_key_exists($routeKey, $routes)) {
    header('HTTP/1.0 404 Not Found');
    echo afficher404();
    exit;
}

[$controllerName, $actionName] = $routes[$routeKey];

if (!class_exists($controllerName) || !method_exists($controllerName, $actionName)) {
    header('HTTP/1.0 404 Not Found');
    echo afficher404();
    exit;
}

$controller = new $controllerName();

// Convention : si un ?id= est présent en GET, on le passe en premier argument
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$id !== null ? $controller->$actionName($id) : $controller->$actionName();

/**
 * Génère la page 404 par défaut.
 */
function afficher404(): string
{
    return <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>404 - Page non trouvée</title>
        <style>
            body { font-family: sans-serif; text-align: center; padding-top: 100px; background-color: #f7f9fa; }
            h1 { color: #e74c3c; font-size: 48px; }
            p { font-size: 18px; color: #555; }
            a { color: #3498db; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>404 Not Found</h1>
        <p>La page demandée n'existe pas ou a été déplacée.</p>
        <p><a href="{$_SERVER['SCRIPT_NAME']}">Retour à l'accueil</a></p>
    </body>
    </html>
    HTML;
}