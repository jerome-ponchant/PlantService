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
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Plant;
use App\Entity\Category;
// src/Controller/PlantQuizController.php

class PlantsController extends AbstractController
{

    public function __construct(
        private PlantRepository $repo,
        private CategoryRepository $categoryRepo,
        private EntityManagerInterface $em
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
            'images' => $plant->getImages(),
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
    $categoryIds = $data['categoryIds'] ?? [];

    // 1. Détermination de la cible (80% échec / 20% hasard)
    $target = null;
    if (rand(1, 10) <= 8 && !empty($failedIds)) {
        $randomKey = array_rand($failedIds);
        $target =$this->repo->findRandomOneFiltered($failedIds, $categoryIds);
    }

    // Si pas de cible (2/10 ou liste vide), on en prend une au hasard
    if (!$target) {
        $target =$this->repo->findRandomOneFiltered([], $categoryIds);;
    }

    // Si la base est vide ou aucune plante ne correspond aux critères
    if (!$target) {
        return $this->json(['error' => 'Aucune plante ne correspond aux catégories sélectionnées'], 404);
    }

    // 2. Construction des options
    // 2.1 La bonne réponse
    $options = [$target];

// 2.2 Essayer de compléter avec d'autres plantes en échec qui respectent les catégories
$otherFailedIds = array_diff($failedIds, [$target->getId()]);
if (!empty($otherFailedIds)) {
    $compatibleFailedOptions = $this->repo->findRandomManyFiltered($otherFailedIds, $categoryIds, 2);
    foreach ($compatibleFailedOptions as $failedOpt) {
        $options[] = $failedOpt;
    }
}

    // 2.3 Compléter avec du hasard jusqu'à 6 options
    $excludeIds = array_map(fn($p) => $p->getId(), $options);
    $needed = 6 - count($options);

    if ($needed > 0) {
        $randoms = $this->repo->findRandomManyFiltered([], $categoryIds, $needed, $excludeIds);
        $options = array_merge($options, $randoms);
    }

    shuffle($options);




    $formatData = function($plant) {
        $imagesData = [];

        foreach ($plant->getImages() as $image) {
            $imagesData[] = [
                'id' => $image->getId(),
                'url' => $image->getUrl(),
                'position' => $image->getPosition(),
            ];
        }

        return [
            'id' => $plant->getId(),
            'name' => $plant->getName(),
            'images' => $imagesData,
        ];
    };

    return $this->json([
        'target' => $formatData($target),
        'options' => array_map($formatData, $options),
        'generated_at' => microtime(true)
    ]);
    }
}
