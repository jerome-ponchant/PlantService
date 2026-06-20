<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;

class PlantIdentificationResponse
{
    public function __construct(
        #[ApiProperty(description: "La description morphologique de la plante")]
        public string $analysis,

        #[ApiProperty(description: "Le nom latin le plus probable")]
        public string $scientificName,

        #[ApiProperty(description: "Le nom commun en français")]
        public ?string $vernacularName,

        #[ApiProperty(description: "La famille botanique (ex: Solanaceae)")]
        public string $familyName
    ) {}
}
