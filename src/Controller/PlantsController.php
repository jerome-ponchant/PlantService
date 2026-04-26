<?php

namespace App\Controller;

// Pour définir les routes avec des attributs #[Route]
use Symfony\Component\Routing\Attribute\Route;

//use Symfony\Component\Routing\Attribute\Route;
// La classe de base pour les contrôleurs (donne accès à $this->json())
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Pour manipuler la réponse (si vous voulez plus de contrôle que $this->json())
use Symfony\Component\HttpFoundation\JsonResponse;

// Pour récupérer les données envoyées par Angular (dans un POST par exemple)
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;

// Pour accéder à vos données via Doctrine
use App\Repository\PlantRepository;
use App\Entity\Plant;
// src/Controller/PlantQuizController.php

class PlantsController extends AbstractController
{

    public function __construct(
        private PlantRepository $repo
    ) {}


    #[Route('/api/quizz/randomPlant', name: 'app_quizz_randomPlant', methods: ['GET'])]
    public function randomPlant(): JsonResponse
    {
        $plant = $this->repo->findRandomOne();

        if (!$plant) {
            return $this->json(['error' => 'Aucune plante en base'], 404);
        }

        // On ne renvoie QUE l'id et l'image pour éviter la triche
        return $this->json([
            'id' => $plant->getId(),
            'name' => $plant->getName(),
            'imageUrl' => $plant->getImageUrl(),
        ]);
    }

    #[Route('/api/quizz/randomPlant/{n}', name: 'app_quizz_manyrandomPlant', methods: ['GET'])]
    public function findRandomPlant(int $n){
         $plants = $this->repo->findRandomMany($n);
         return $this->json($plants);
    }

}
