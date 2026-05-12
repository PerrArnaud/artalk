<?php

namespace App\Repository;

use App\Entity\MOTW;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MOTW>
 */
class MOTWRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MOTW::class);
    }

    public function findByArtType(?int $artTypeId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.DatePost', 'DESC');

        if ($artTypeId !== null) {
            $qb->andWhere('m.artType = :artTypeId')
               ->setParameter('artTypeId', $artTypeId);
        }

        return $qb->getQuery()->getResult();
    }
}
