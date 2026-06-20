<?php
// Désactiver le cache navigateur pour ce script de débug
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// 1. Remonter d'un niveau pour localiser l'autoloader de Composer et les données d'installation
$projectDir = dirname(__DIR__);
$autoloader = $projectDir . '/vendor/autoload.php';
$installedJsonPath = $projectDir . '/vendor/composer/installed.json';

if (!file_exists($autoloader)) {
    die("Erreur : Le dossier vendor est introuvable. Exécutez 'composer install' ou vérifiez votre FTP.");
}

require_once $autoloader;

use Symfony\Component\Dotenv\Dotenv;

// 2. Détection et lecture des fichiers .env à la racine
$dotenvFiles = [
    '.env',
    '.env.local',
    '.env.dev',
    '.env.dev.local',
    '.env.prod',
    '.env.prod.local'
];

$loadedFiles = [];
if (class_exists(Dotenv::class)) {
    $dotenv = new Dotenv();
    foreach ($dotenvFiles as $file) {
        $filePath = $projectDir . '/' . $file;
        if (file_exists($filePath)) {
            $dotenv->load($filePath);
            $loadedFiles[] = [
                'name' => $file,
                'path' => realpath($filePath),
                'mtime' => date("d/m/Y H:i:s", filemtime($filePath))
            ];
        }
    }
}

// 3. Extraction des versions de Symfony et de ses Bundles depuis installed.json
$symfonyPackages = [];
$symfonyBundles = [];

if (file_exists($installedJsonPath)) {
    $installedData = json_decode(file_get_contents($installedJsonPath), true);
    // Selon la version de Composer, les packages sont soit directement à la racine, soit dans une clé 'packages'
    $packages = $installedData['packages'] ?? $installedData ?? [];

    foreach ($packages as $pkg) {
        $name = $pkg['name'] ?? '';
        $version = $pkg['version'] ?? 'Inconnue';

        // On cible tout ce qui appartient à l'écosystème Symfony ou aux Bundles tiers
        if (str_starts_with($name, 'symfony/') || str_contains($name, '-bundle') || str_contains($name, 'bundle')) {
            // Séparation visuelle entre composants natifs Symfony et Bundles additionnels
            if (str_contains($name, '-bundle') || str_contains($name, 'bundle') || $name === 'api-platform/core') {
                $symfonyBundles[$name] = $version;
            } else {
                $symfonyPackages[$name] = $version;
            }
        }
    }
    ksort($symfonyPackages);
    ksort($symfonyBundles);
}

// 4. Récupération de l'ensemble des variables d'environnement actives
$allVariables = array_merge($_SERVER, $_ENV);
ksort($allVariables);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Analyseur d'environnement et de versions direct</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f6f9; color: #333; padding: 30px; }
        .container { max-width: 1100px; margin: 0 auto; }
        h1, h2 { color: #1e293b; }
        .card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.95em; }
        th { background: #334155; color: white; }
        tr:hover { background: #f8fafc; }
        code { background: #fee2e2; color: #991b1b; padding: 2px 5px; border-radius: 4px; font-family: monospace; font-size: 0.9em; word-break: break-all; }
        .badge { background: #def7ec; color: #03543f; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .badge-version { background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; font-family: monospace; }
        .badge-bundle { background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .system { color: #94a3b8; font-style: italic; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Diagnostic d'environnement & Dépendances</h1>

    <div class="card">
        <h2>1. Fichiers de configuration physiques trouvés à la racine</h2>
        <?php if (empty($loadedFiles)): ?>
            <p style="color:red;">⚠️ Aucun fichier .env trouvé dans <code><?php echo htmlspecialchars($projectDir); ?></code></p>
        <?php else: ?>
            <ul>
                <?php foreach ($loadedFiles as $f): ?>
                    <li>
                        <span class="badge">Chargé</span>
                        <strong><?php echo htmlspecialchars($f['name']); ?></strong>
                        <span class="system">— Modifié le : <?php echo $f['mtime']; ?> (<?php echo htmlspecialchars($f['path']); ?>)</span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="grid">
        <div class="card">
            <h2>2. Version des Bundles & API</h2>
            <?php if (empty($symfonyBundles)): ?>
                <p class="system">Aucun bundle détecté ou fichier installed.json manquant.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom du Bundle</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($symfonyBundles as $name => $version): ?>
                            <tr>
                                <td><span class="badge-bundle">Bundle</span> <strong><?php echo htmlspecialchars($name); ?></strong></td>
                                <td><span class="badge-version"><?php echo htmlspecialchars($version); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>3. Composants Coeur Symfony (Libreries)</h2>
            <?php if (empty($symfonyPackages)): ?>
                <p class="system">Aucun composant détecté ou fichier installed.json manquant.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Composant</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($symfonyPackages as $name => $version): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                                <td><span class="badge-version"><?php echo htmlspecialchars($version); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>4. Valeurs des variables en mémoire vive</h2>
        <p>Environnement détecté : <code>APP_ENV = <?php echo htmlspecialchars($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'non défini'); ?></code></p>
        <table>
            <thead>
                <tr>
                    <th>Variable</th>
                    <th>Valeur actuelle en runtime</th>
                    <th>Origine</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allVariables as $key => $value): ?>
                    <?php
                    // Correction du bug d'affichage si la variable est un tableau (ex: argv)
                    if (is_array($value)) {
                        $displayValue = json_encode($value);
                    } else {
                        $displayValue = (string)$value;
                    }

                    // Masquage des données sensibles
                    if (preg_match('/(PASSWORD|DATABASE_URL|SECRET|KEY|JWT)/i', $key)) {
                        $displayValue = '•••••••• (Masqué pour des raisons de sécurité)';
                    }

                    // Détection origine
                    $isSystem = str_starts_with($key, 'HTTP_') || str_starts_with($key, 'SERVER_') || str_starts_with($key, 'DOCUMENT_');
                    $origine = $isSystem ? '<span class="system">Serveur (OVH/Apache)</span>' : 'Fichier .env / Application';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($displayValue); ?></code></td>
                        <td><?php echo $origine; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
