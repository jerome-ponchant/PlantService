<?php
header('Content-Type: text/plain');

echo "--- INFOS UTILISATEUR APACHE/PHP ---\n\n";

// 1. Propriétaire du script actuel
echo "Propriétaire du script (get_current_user) : " . get_current_user() . "\n";

// 2. Utilisateur système qui exécute PHP (via une astuce d'écriture de fichier temporaire)
$tempFile = tempnam(sys_get_temp_dir(), 'ovh_test_');
if ($tempFile) {
    $ownerId = fileowner($tempFile);
    echo "UID système de l'exécuteur : " . $ownerId . "\n";

    // Si la fonction de traduction d'UID existe, on l'utilise, sinon on l'esquive
    if (function_exists('posix_getpwuid')) {
        $userInfo = posix_getpwuid($ownerId);
        echo "Nom système de l'exécuteur : " . ($userInfo['name'] ?? 'Inconnu') . "\n";
    } else {
        echo "Nom système de l'exécuteur : (posix_getpwuid désactivé)\n";
    }
    unlink($tempFile);
}

echo "\n--- TEST DE DROITS D'ÉCRITURE ---\n\n";

// 3. Test de création de dossier dans le répertoire courant
$testDir = __DIR__ . '/test_droits_apache';
if (!file_exists($testDir)) {
    if (@mkdir($testDir, 0755)) {
        echo "✅ SUCCÈS : PHP a le droit de créer un dossier ici.\n";
        rmdir($testDir);
    } else {
        echo "❌ ÉCHEC : PHP n'a PAS le droit de créer de dossier ici (Problème de Chmod).\n";
    }
}
