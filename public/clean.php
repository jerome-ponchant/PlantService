<?php
define('CACHE_DIR', dirname(__DIR__) . '/var/cache');

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) rrmdir($dir . "/" . $object);
                else unlink($dir . "/" . $object);
            }
        }
        rmdir($dir);
    }
}

if (is_dir(CACHE_DIR)) {
    rrmdir(CACHE_DIR);
    echo "Le cache de Symfony a été entièrement nettoyé avec succès !";
} else {
    echo "Le dossier cache n'existait pas ou était déjà vide.";
}
