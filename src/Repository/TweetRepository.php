<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tweet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Tweet> */
class TweetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tweet::class);
    }

    public function create(Tweet $tweet): void
    {
        $this->getEntityManager()->persist($tweet);
        $this->getEntityManager()->flush();
    }
}
