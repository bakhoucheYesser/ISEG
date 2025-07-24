<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class BaseRepository extends ServiceEntityRepository
{
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTerm(string $searchTerm, array $fields = ['name']): array
    {
        $qb = $this->createQueryBuilder('e');

        $conditions = $qb->expr()->orX();
        foreach ($fields as $field) {
            $conditions->add($qb->expr()->like("e.$field", ':searchTerm'));
        }

        return $qb->where($conditions)
            ->setParameter('searchTerm', "%$searchTerm%")
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function paginate(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('e')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
