<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Récupère les commandes du client avec les détails des produits en 1 requête
     */
    public function findDashboardOrders($user): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.orderItems', 'oi')->addSelect('oi')
            ->leftJoin('o.payments', 'p')->addSelect('p')
            ->where('o.user = :user')
            ->orderBy('o.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
