<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Controller\IdentifyController;
use App\Dto\PlantIdentificationResponse;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'PlantIdentification',
    paginationEnabled: false
)]
#[Post(
    uriTemplate: '/identify',
    controller: IdentifyController::class,
    inputFormats: ['multipart' => ['multipart/form-data']],
    output: PlantIdentificationResponse::class,
    deserialize: false,
    // On passe un vrai objet Operation au lieu d'un tableau
    openapi: new Operation(
        summary: "Identifie une plante à partir d'un tableau d'images",
        description: "Envoie un ou plusieurs fichiers images (Form Data) pour obtenir l'identification botanique.",
        requestBody: new RequestBody(
            content: new \ArrayObject([
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'images' => [
                                'type' => 'array',
                                'items' => ['type' => 'string', 'format' => 'binary'],
                                'description' => 'Tableau de fichiers images (JPEG, PNG)'
                            ]
                        ]
                    ]
                ]
            ])
        )
    )
)]
class PlantIdentification
{
    /**
     * @var array<\Symfony\Component\HttpFoundation\File\UploadedFile>
     */
    #[Assert\All([
        new Assert\Image(maxSize: '8m', mimeTypes: ['image/jpeg', 'image/png'])
    ])]
    public array $images = [];
}
