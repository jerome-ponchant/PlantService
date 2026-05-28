<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Dotenv\Dotenv;

class EnvDebugController extends AbstractController
{

    public function __construct(private LoggerInterface $logger){}

    #[Route('/debug/env', name: 'app_debug_env', methods: ['GET'])]
    public function index(): Response
    {
        $this->logger->log(1,"Test",[]);
        // 1. Détection des fichiers .env potentiels à la racine du projet
        $projectDir = $this->getParameter('kernel.project_dir');
        $envFiles = [
            '.env',
            '.env.local',
            '.env.' . $_ENV['APP_ENV'],
            '.env.' . $_ENV['APP_ENV'] . '.local'
        ];

        $detectedFiles = [];
        foreach ($envFiles as $file) {
            $path = $projectDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                $detectedFiles[] = $file . ' (' . realpath($path) . ')';
            }
        }

        // 2. Récupération et tri de toutes les variables d'environnement
        $envVariables = array_merge($_SERVER, $_ENV);
        ksort($envVariables);

        // 3. Construction d'un affichage HTML propre et lisible
        $html = '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Symfony Env Debugger</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f9f9fb; color: #2d3748; padding: 40px; }
                h1, h2 { color: #1a202c; }
                .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 30px; }
                ul { padding-left: 20px; line-height: 1.6; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #edf2f7; }
                th { background-color: #4a5568; color: white; font-weight: 600; }
                tr:hover { background-color: #f7fafc; }
                code { background: #edf2f7; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9em; color: #e53e3e; }
                .system { color: #718096; font-style: italic; }
            </style>
        </head>
        <body>
            <h1>Débugger de Variables d\'Environnement</h1>

            <div class="card">
                <h2>1. Fichiers de configuration détectés</h2>
                <p>Symfony analyse ces fichiers à la racine dans l\'ordre suivant (les fichiers <code>.local</code> écrasent les autres) :</p>
                <ul>';
                foreach ($detectedFiles as $file) {
                    $html .= '<li><strong>' . htmlspecialchars($file) . '</strong></li>';
                }
        $html .= '</ul>
            </div>

            <div class="card">
                <h2>2. Variables d\'environnement actives</h2>
                <p>Environnement actuel de l\'application : <code>' . htmlspecialchars($_ENV['APP_ENV'] ?? 'non défini') . '</code></p>
                <table>
                    <thead>
                        <tr>
                            <th>Clé de la variable</th>
                            <th>Valeur actuelle</th>
                            <th>Provenance probable</th>
                        </tr>
                    </thead>
                    <tbody>';

                    foreach ($envVariables as $key => $value) {
                        // Masquer les informations sensibles comme les mots de passe de base de données
                        $displayValue = $value;
                        if (preg_match('/(PASSWORD|DATABASE_URL|SECRET|KEY)/i', $key)) {
                            $displayValue = '•••••••• (Masqué pour des raisons de sécurité)';
                        }

                        if (is_array($displayValue)) {
                            $displayValue = json_encode($displayValue);
                        }

                        // Tenter de deviner si la variable vient du .env ou du système
                        $provenance = 'Fichier .env ou Contexte';
                        if (str_starts_with($key, 'HTTP_') || str_starts_with($key, 'SERVER_') || str_starts_with($key, 'DOCUMENT_')) {
                            $provenance = '<span class="system">Serveur Apache / OVH</span>';
                        }

                        $html .= '<tr>
                            <td><strong>' . htmlspecialchars($key) . '</strong></td>
                            <td><code>' . htmlspecialchars((string)$displayValue) . '</code></td>
                            <td>' . $provenance . '</td>
                        </tr>';
                    }

        $html .= '</tbody>
                </table>
            </div>
        </body>
        </html>';

        return new Response($html);
    }
}
