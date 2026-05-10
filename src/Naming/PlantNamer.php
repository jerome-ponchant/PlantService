<?php
namespace App\Naming;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class PlantNamer implements NamerInterface
{
    public function name($object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);
        // On récupère le nom depuis l'entité Plant
        $name = $object->getName();
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));

        return $safeName . '-' . uniqid() . '.' . $file->guessExtension();
    }
}
