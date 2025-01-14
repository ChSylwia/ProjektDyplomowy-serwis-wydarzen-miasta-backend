<?php

namespace App\Repository;

use App\Entity\GoogleIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GoogleIntegration>
 *
 * @method GoogleIntegration|null find($id, $lockMode = null, $lockVersion = null)
 * @method GoogleIntegration|null findOneBy(array $criteria, array $orderBy = null)
 * @method GoogleIntegration[]    findAll()
 * @method GoogleIntegration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GoogleIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoogleIntegration::class);
    }

    public function save(GoogleIntegration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Add custom methods here
}
