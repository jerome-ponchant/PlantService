<?php

use App\Kernel;

// 1. Capture de l'illusion générée par le .htaccess racine
$mockUri = $_SERVER['REDIRECT_MOCK_URI'] ?? $_SERVER['MOCK_URI'] ?? null;

if ($mockUri) {
    // On nettoie les éventuels doubles slashes pour Api Platform
    $_SERVER['REQUEST_URI'] = str_replace('//', '/', $mockUri);

    // CAS OVH MUTUALISÉ / DOCUMENTROOT GLOBAL :
    // On force Symfony à croire que l'index est à la racine réelle (/)
    // pour éviter le décalage de routage lié au sous-dossier api/public
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

require_once dirname(__DIR__).'/vendor/autoload-runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
// Le fichier doit s'arrêter strictement ICI. Aucune accolade ne doit suivre.
