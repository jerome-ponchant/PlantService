<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class PlantSearchFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $alias = $queryBuilder->getRootAliases()[0];

        // 1. Gestion de la recherche textuelle globale (Nom ou Nom Vernaculaire)
        if ($property === 'search' && !empty($value)) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $queryBuilder
                ->andWhere(sprintf('%s.name LIKE :%s OR %s.commonName LIKE :%s', $alias, $parameterName, $alias, $parameterName))
                ->setParameter($parameterName, '%' . $value . '%');
        }

        // 2. Gestion du filtre par catégories cumulatives (Intersection / AND)
        if ($property === 'filterCategories' && !empty($value)) {
            // Nettoyage au cas où les données contiennent des crochets [ ] ou des espaces
            $cleanValue = str_replace(['[', ']'], '', $value);
            $categoryIds = array_filter(array_map('intval', explode(',', $cleanValue)));

            if (!empty($categoryIds)) {
                $parameterName = $queryNameGenerator->generateParameterName($property);

                // Création d'une sous-requête isolée pour trouver les IDs des plantes
                // possédant l'intégralité des catégories sélectionnées
                $subQb = $queryBuilder->getEntityManager()->createQueryBuilder();
                $subQb->select('p_sub.id')
                    ->from($resourceClass, 'p_sub')
                    ->innerJoin('p_sub.categories', 'c_sub')
                    ->where('c_sub.id IN (:' . $parameterName . ')')
                    ->groupBy('p_sub.id')
                    ->having('COUNT(DISTINCT c_sub.id) = ' . count($categoryIds));

                // On applique le résultat de cette sous-requête à la requête principale
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->in($alias . '.id', $subQb->getDQL()))
                    ->setParameter($parameterName, $categoryIds);
            }
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Recherche par nom international ou vernaculaire',
            ],
            'filterCategories' => [
                'property' => 'filterCategories',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'IDs de catégories (ex: 12 ou 12,13) pour une intersection (AND)',
            ],
        ];
    }
}
