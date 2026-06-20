<?php

namespace App\Service\External;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PlantNetClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $plantNetKey // Injecté via services.yaml
    ) {}

    /**
     * Identifie une plante à partir d'un fichier uploadé par le front-end
     */
    public function identify(UploadedFile $file, string $organ = 'flower', string $project = 'weurope'): array
    {
        // Préparation du formulaire multipart (binaire de l'image + métadonnées)
        $formFields = [
            'images' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName(), $file->getClientMimeType()),
            'organs' => $organ,
        ];

        $formData = new FormDataPart($formFields);

        $response = $this->httpClient->request('POST', "https://my-api.plantnet.org/v2/identify/{$project}", [
            'query' => [
                'api-key' => $this->plantNetKey,
            ],
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->toArray(); // Contient les scores et les propositions taxonomiques
    }
}
