<?php

namespace App\Repository;

use App\Entity\Unite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Unite>
 */
class UniteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Unite::class);
    }

    //    /**
    //     * @return Unite[] Returns an array of Unite objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Unite
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Récupère toutes les unités avec leurs baies et locataires en UNE SEULE requête.
     */
    public function findAllWithBaieAndLocataire(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.baie', 'b')
            ->addSelect('b')
            ->leftJoin('u.locataire', 'l')
            ->addSelect('l')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère des unités disponibles et les VERROUILLE pour éviter les conflits (Race Condition)
     */
    public function findAndLockAvailableUnites(int $limit): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.locataire IS NULL')
            ->setMaxResults($limit)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();
    }

    /**
     * Récupère les unités du client avec leurs baies et interventions en 1 requête
     */
    public function findDashboardUnites($user): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.baie', 'b')->addSelect('b')
            ->leftJoin('u.interventions', 'i')->addSelect('i')
            ->where('u.locataire = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
