<?php

namespace App\Repository;

use App\Entity\ActiveLocalEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiveLocalEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActiveLocalEvent::class);
    }

    // You can add custom query methods here if needed
}
