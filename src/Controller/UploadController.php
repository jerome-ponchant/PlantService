<?php

namespace App\Controller;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Pour manipuler la réponse (si vous voulez plus de contrôle que $this->json())
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
// Pour récupérer les données envoyées par Angular (dans un POST par exemple)
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
class UploadController extends AbstractController{

    // src/Controller/PlantsController.php

    public function __construct(private LoggerInterface $logger){}

#[Route('/api/upload', name: 'app_upload', methods: ['POST'])]
public function upload(Request $request): JsonResponse
{
    // 1. Récupération du fichier et du nom envoyés par Angular
    $uploadedFile = $request->files->get('file');
    $relativeNamePath = $request->request->get('name', 'nom-manquant');

    // --- Récupération du paramètre d'environnement ---
    //$uploadDir = $this->getParameter('upload_dir');
    $uploadDir = $_ENV['UPLOAD_DIR'] ?? 'uploads/';

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
// 3. Définition du dossier de destination

    $documentRoot = $request->server->get('DOCUMENT_ROOT');
    $destinationDir = $documentRoot."/".$uploadDir;

$projectDir = $this->getParameter('kernel.project_dir');
$projectDir = rtrim($projectDir, '/');





// Sécurisation du sous-dossier (évite les injections de type ../)
$safeSubDir = str_replace('..', '', $subDirectory);
$safeSubDir = trim($safeSubDir, '/');

if (!empty($safeSubDir)) {
    $finalDestination = $destinationDir . '/' . $safeSubDir;
} else {
    $finalDestination = $destinationDir;
}
    // 4. Création du fichier
    $this->logger->info("Tentative de création de fichier",["finalDestination" => $finalDestination, "fileName"=>$fileName]);
    $filesystem = new Filesystem();
    if (!$filesystem->exists($finalDestination)) {
        $filesystem->mkdir($finalDestination, 0755);
    }

    try {
        $uploadedFile->move($finalDestination, $fileName);

        $this->logger->info("Succès de création de fichier",["finalDestination" => $finalDestination, "fileName"=>$fileName]);

    } catch (\Exception $e) {
        $this->logger->info("Echec de création de fichier",["finalDestination" => $finalDestination, "fileName"=>$fileName, "exception"=>$e]);

        return $this->json(['error' => 'Impossible de sauvegarder le fichier'], 500);

    }

    // 4. On retourne le chemin relatif pour qu'Angular puisse l'enregistrer en BDD
    return $this->json([
        'path' => ($safeSubDir ? $safeSubDir . '/' : '') . $fileName,
	'finalDestination' => $finalDestination,
	'fileName' => $fileName,
	'documentRoot' => $documentRoot
    ]);
}
#[Route('/api/upload/prefix', name: 'app_upload_prefix', methods: ['GET'])]
    public function getPrefix(Request $request): JsonResponse
    {
        //$uploadDir = $this->getParameter('upload_dir');
        $uploadDir = $_ENV['UPLOAD_DIR'] ?? 'uploads/';

        return $this->json([
            'prefix' => $request->getSchemeAndHttpHost() . '/' . ltrim($uploadDir, '/')
        ]);
    }

}
