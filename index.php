<?php
define('ROOT', __DIR__); // ✅ Ne rien ajouter après __DIR__
require_once __DIR__ . '/app/core/env.php';

// --- Erreurs : affichées en local pour déboguer, jamais en production ---
// (bascule via APP_ENV dans .env — "local" affiche les erreurs, tout le reste les masque et les journalise)
error_reporting(E_ALL);
if (getenv('APP_ENV') === 'local') {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT . '/logs/php_errors.log');
}
// --------------------------------------------------------------------

// Fuseau horaire de l'application fixé une bonne fois pour toutes (le serveur peut être
// configuré sur un autre fuseau, ex: Europe/Berlin, ce qui décale "aujourd'hui" de 1-2h
// et casse tous les filtres par date du jour comme dans Envoi_colis/Programmation_voyages).
date_default_timezone_set('Africa/Bamako');

session_start();

// Injecte automatiquement le tag PWA (manifest + service worker + modal d'installation)
// juste avant </body> sur toutes les pages HTML rendues, sans toucher chaque vue.
// N'affecte jamais les réponses JSON/AJAX puisqu'elles ne contiennent pas de balise </body>.
ob_start(function ($html) {
    if (stripos($html, '</body>') === false) {
        return $html;
    }
    $tag = '<script>window.PWA_BASE_URL = ' . json_encode(BASE_URL) . ';'
        . 'window.PWA_USER_LOGGED_IN = ' . json_encode(isset($_SESSION['id_utilisateur'])) . ';</script>' . "\n"
        . '<script src="' . ASSET_URL . '/mon_js/pwa-install.js" defer></script>' . "\n</body>";
    return preg_replace('/<\/body>/i', $tag, $html, 1);
});

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/core/autoload.php';

$app = new App();

ob_end_flush();

// Récupérer le controller et l'action depuis CLI si nécessaire
if (php_sapi_name() === 'cli') {
    if (isset($argv[1]) && isset($argv[2])) {
        $_GET['controller'] = $argv[1];
        $_GET['action'] = $argv[2];
    }
}

// Pour URL classique (HTTP)
$url_path = $_GET['controller'] ?? 'home';   // controller par défaut
$action = $_GET['action'] ?? 'index';        // action par défaut





 
