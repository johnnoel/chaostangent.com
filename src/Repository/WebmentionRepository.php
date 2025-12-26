<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Webmention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Webmention>
 */
class WebmentionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Webmention::class);
    }

    public function create(Webmention $webmention): void
    {
        $this->getEntityManager()->persist($webmention);
        $this->getEntityManager()->flush();
    }
}
