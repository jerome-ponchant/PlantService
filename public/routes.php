<?php
use App\Kernel;
use Symfony\Component\Runtime\RuntimeInterface;

// 1. Définition du chemin vers l'autoloader de Symfony
$autoloadPath = dirname(__DIR__) . '/vendor/autoload_runtime.php';

if (!file_exists($autoloadPath)) {
    die("Erreur : L'autoloader de Symfony est introuvable. Vérifiez l'emplacement du fichier.");
}

require_once $autoloadPath;

// 2. Initialisation du Kernel Symfony en récupérant l'environnement actuel
return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();

    // 3. Récupération du conteneur de services et du routeur
    $container = $kernel->getContainer();
    // Utilisation du conteneur privé si nécessaire pour récupérer le routeur
    $router = $container->has('router') ? $container->get('router') : $kernel->getContainer()->get('test.service_container')->get('router');
    $routeCollection = $router->getRouteCollection();

    // 4. Construction de l'affichage HTML
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Liste des Routes Symfony / API Platform</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f4f6f9; color: #333; }
            h1 { color: #1a1a1a; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #2c3e50; color: white; }
            tr:hover { background-color: #f1f1f1; }
            .method { font-weight: bold; color: #27ae60; }
            .path { font-family: monospace; font-size: 1.1em; color: #c0392b; }
        </style>
    </head>
    <body>
        <h1>Routes détectées par le Framework</h1>
        <table>
            <thead>
                <tr>
                    <th>Nom de la Route</th>
                    <th>Méthodes</th>
                    <th>URL Path (Chemin)</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($routeCollection as $name => $route) {
        // Filtrer ou nettoyer l'affichage des méthodes HTTP
        $methods = implode(', ', $route->getMethods()) ?: 'ANY';
        $path = $route->getPath();

        echo "<tr>
                <td><strong>{$name}</strong></td>
                <td class='method'>{$methods}</td>
                <td class='path'>{$path}</td>
              </tr>";
    }

    echo "  </tbody>
        </table>
    </body>
    </html>";

    exit;
};
