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

   // src/Controller/PlantApiController.php

#[Route('/api/quizz/build-options', methods: ['POST'])]
public function buildOptions(Request $request): JsonResponse
{
    // On récupère les données envoyées en JSON par Angular
    $data = json_decode($request->getContent(), true);
    $failedIds = $data['failedIds'] ?? [];

    // 1. Détermination de la cible (80% échec / 20% hasard)
    $target = null;
    if (rand(1, 10) <= 8 && !empty($failedIds)) {
        $randomKey = array_rand($failedIds);
        $target = $this->repo->find($failedIds[$randomKey]);
    }

    // Si pas de cible (2/10 ou liste vide), on en prend une au hasard
    if (!$target) {
        $target = $this->repo->findRandomOne();
    }

    // 2. Construction des options
    // 2.1 La bonne réponse
    $options = [$target];

    // 2.2 Deux autres plantes en échec (exclure la cible)
    $otherFailedIds = array_diff($failedIds, [$target->getId()]);
    if (!empty($otherFailedIds)) {
        $randomKeys = (array) array_rand($otherFailedIds, min(2, count($otherFailedIds)));
        foreach ($randomKeys as $key) {
            $options[] = $this->repo->find($otherFailedIds[$key]);
        }
    }

    // 2.3 Compléter avec du hasard jusqu'à 6 options
    $excludeIds = array_map(fn($p) => $p->getId(), $options);
    $needed = 6 - count($options);
    if ($needed > 0) {
        $randoms = $this->repo->findRandomManyExcluded($needed, $excludeIds);
        $options = array_merge($options, $randoms);
    }

    shuffle($options);

    return $this->json([
        'target' => $target,
        'options' => $options
    ]);
}


}
