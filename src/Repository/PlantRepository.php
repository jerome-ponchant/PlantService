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
    //    /**
    //     * @return Plant[] Returns an array of Plant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Plant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
