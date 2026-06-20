<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsController]
class IdentifyController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->geminiApiKey = $geminiApiKey;
    }

    public function __invoke(Request $request): JsonResponse
    {
        set_time_limit(120);

        // Récupération du tableau de fichiers
        $uploadedFiles = $request->files->get('images');

// --- AJOUTE CE BLOC DE DEBUG ---
error_log("=== DEBUG SWAGGER / SYMFONY ===");
error_log("Content-Type de la requête : " . $request->headers->get('Content-Type'));
error_log("Nombre de fichiers reçus sous la clé 'images' : " . (is_array($uploadedFiles) ? count($uploadedFiles) : ($uploadedFiles ? '1 (pas un tableau)' : '0')));
error_log("Contenu global de la superglobale \$_FILES : " . print_r($_FILES, true));
// -------------------------------

        if (empty($uploadedFiles)) {
            throw new BadRequestException("Le tableau d'images est vide.");
        }

        // Si Symfony reçoit un fichier unique (UploadedFile) au lieu d'un array,
        // on le transforme en array pour sécuriser le foreach ci-dessous.
        if ($uploadedFiles instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $uploadedFiles = [$uploadedFiles];
        }

        $parts = [];

        // Le prompt devient plus simple car on délègue la structure au moteur de l'API
        $promptText = "Tu es un expert en botanique. Analyse l'image fournie (port, branches, aiguilles/feuilles, écorce, fleurs ou fruits) et identifie ce spécimen. "
            . "Regarde attentivement les formes et les couleurs réelles de l'image, ne devine pas au hasard. "
            . "Remplis scrupuleusement les champs demandés dans le schéma de réponse.";

        $parts[] = ['text' => $promptText];

        // Encodage des images en Base64
        foreach ($uploadedFiles as $file) {
            if ($file) {
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $file->getMimeType(),
                        'data' => base64_encode(file_get_contents($file->getPathname()))
                    ]
                ];
            }
        }

        // Utilisation du endpoint Gemini
        //$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
        // Remplacer v1 par v1beta
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

        try {
            $response = $this->httpClient->request('POST', $url, [
                'query' => [
                    'key' => $this->geminiApiKey
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => $parts
                        ]
                    ],
                    // --- CONFIGURATION VALIDE POUR L'API v1beta ---
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'OBJECT', // En majuscules pour le schéma v1beta
                            'properties' => [
                                'analysis' => [
                                    'type' => 'STRING',
                                    'description' => 'Description des criteres observes sur l image.'
                                ],
                                'scientificName' => [
                                    'type' => 'STRING',
                                    'description' => 'Le nom scientifique latin (Genre espece).'
                                ],
                                'vernacularName' => [
                                    'type' => 'STRING',
                                    'description' => 'Le nom commun en francais.'
                                ],
                                'familyName' => [
                                    'type' => 'STRING',
                                    'description' => 'Le nom de la famille botanique.'
                                ]
                            ],
                            'required' => ['analysis', 'scientificName', 'familyName']
                        ]
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 503) {
                throw new \RuntimeException("Le service d'identification est momentanément surchargé.");
            }

            if ($statusCode !== 200) {
                $errorBody = $response->getContent(false);
                throw new \RuntimeException("Réponse Google non valide (Code " . $statusCode . ") : " . $errorBody);
            }

            $result = $response->toArray();
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'];

            error_log("--- RÉPONSE BRUTE GEMINI (DÉJÀ DU JSON PROPRE) --- " . $rawText);

            // Plus besoin de nettoyer les balises markdown (```json ... ```) !
            // L'API garantit que $rawText est une chaîne JSON valide respectant ton schéma.
            $data = json_decode($rawText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Erreur de décodage interne du JSON renvoyé par l'API : " . json_last_error_msg());
            }

            // Retour direct au client (ou vers ton entité Symfony/Doctrine)
            return $this->json([
                'analysis' => $data['analysis'] ?? 'Aucune analyse fournie',
                'scientificName' => $data['scientificName'] ?? 'Inconnu',
                'vernacularName' => $data['vernacularName'] ?? null,
                'familyName' => $data['familyName'] ?? 'Inconnu'
            ]);

        } catch (\Exception $e) {
            throw new \RuntimeException("Échec de l'analyse d'image : " . $e->getMessage());
        }
    }
}
