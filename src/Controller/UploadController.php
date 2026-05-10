<?php

namespace App\Controller;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Pour manipuler la réponse (si vous voulez plus de contrôle que $this->json())
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
// Pour récupérer les données envoyées par Angular (dans un POST par exemple)
use Symfony\Component\HttpFoundation\Request;
class UploadController extends AbstractController{

    // src/Controller/PlantsController.php

#[Route('/api/upload', name: 'app_upload', methods: ['POST'])]
public function upload(Request $request): JsonResponse
{
    // 1. Récupération du fichier et du nom envoyés par Angular
    $uploadedFile = $request->files->get('file');
    $relativeNamePath = $request->request->get('name', 'nom-manquant');

    // --- Récupération du paramètre d'environnement ---
    $uploadDir = $this->getParameter('upload_dir');

    if (!$uploadedFile) {
        return $this->json(['error' => 'Aucun fichier reçu'], 400);
    }

    // 2. Nettoyage du nom pour le fichier (ex: "Rose Rouge" -> "rose-rouge")
    $pathParts = explode('/', $relativeNamePath);
    $rawPlantName = array_pop($pathParts);
    $subDirectory = implode('/', $pathParts);

    $safePlantName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $rawPlantName));
    $fileName = $safePlantName . '.' . $uploadedFile->guessExtension();

    // 3. Définition du dossier de destination
    $baseDestination = $this->getParameter('kernel.project_dir') . '/public/' . trim($uploadDir, '/');

    $safeSubDir = str_replace('..', '', $subDirectory);
    $finalDestination = rtrim($baseDestination . '/' . $safeSubDir, '/');

    // 4. Création du fichier
    $filesystem = new Filesystem();
    if (!$filesystem->exists($finalDestination)) {
        $filesystem->mkdir($finalDestination, 0755);
    }

    try {
        $uploadedFile->move($finalDestination, $fileName);
    } catch (\Exception $e) {
        return $this->json(['error' => 'Impossible de sauvegarder le fichier'], 500);
    }

    // 4. On retourne le chemin relatif pour qu'Angular puisse l'enregistrer en BDD
    return $this->json([
        'path' => ($safeSubDir ? $safeSubDir . '/' : '') . $fileName
    ]);
}
#[Route('/api/upload/prefix', name: 'app_upload_prefix', methods: ['GET'])]
    public function getPrefix(Request $request): JsonResponse
    {
        $uploadDir = $this->getParameter('upload_dir');

        return $this->json([
            'prefix' => $request->getSchemeAndHttpHost() . '/' . ltrim($uploadDir, '/')
        ]);
    }

}
