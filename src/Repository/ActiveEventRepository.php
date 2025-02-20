<?php

namespace App\Repository;

use App\Entity\ActiveEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiveEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActiveEvent::class);
    }

    // You can add custom query methods here if needed
}
