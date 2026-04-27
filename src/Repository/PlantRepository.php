<?php

namespace App\Repository;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Plant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends ServiceEntityRepository<Plant>
 */
class PlantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plant::class);
    }

    // Dans src/Repository/PlantRepository.php
    public function findRandomOne(): ?Plant
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($count == 0)
            return null;

        return $this->createQueryBuilder('p')
            ->setFirstResult(rand(0, $count - 1)) // On saute un nombre de lignes au hasard
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRandomMany(int $limit): array
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($count == 0) {
            return [];
        }

        // Si on demande plus de plantes qu'il n'en existe, on cap la limite
        $limit = min($limit, $count);

        // On calcule un offset aléatoire sécurisé
        // (Le max possible est le nombre total moins le nombre demandé)
        $maxOffset = max(0, $count - $limit);
        $offset = rand(0, $maxOffset);

        return $this->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
 * Retourne un nombre défini de plantes au hasard,
 * en excluant une liste d'identifiants spécifiques.
 * * @param int $limit Le nombre de résultats souhaités
 * @param int[] $excludeIds Les IDs à ne pas inclure dans la sélection
 * @return Plant[]
 */
public function findRandomManyExcluded(int $limit, array $excludeIds): array
{
    $qb = $this->createQueryBuilder('p');

    // On exclut les IDs seulement si le tableau n'est pas vide
    if (!empty($excludeIds)) {
        $qb->andWhere('p.id NOT IN (:ids)')
           ->setParameter('ids', $excludeIds);
    }

    // 1. On compte d'abord combien d'entrées répondent au critère d'exclusion
    // On clone le QueryBuilder pour ne pas polluer la requête finale
    $countQb = clone $qb;
    $total = $countQb->select('COUNT(p.id)')
                     ->getQuery()
                     ->getSingleScalarResult();

    if ($total == 0) {
        return [];
    }

    // 2. On ajuste la limite si elle est supérieure au nombre de lignes disponibles
    $effectiveLimit = min($limit, $total);

    // 3. On calcule un offset aléatoire sécurisé
    $maxOffset = max(0, $total - $effectiveLimit);
    $offset = rand(0, $maxOffset);

    // 4. On exécute la requête finale avec le saut de lignes
    return $qb->setFirstResult($offset)
              ->setMaxResults($effectiveLimit)
              ->getQuery()
              ->getResult();
}
}
