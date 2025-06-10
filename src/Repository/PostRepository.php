<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Collection<int,Post>
     */
    public function getHomepagePosts(int $page, int $perPage): Collection
    {
        $qb = $this->createQueryBuilder('p');
        /** @var array<Post> $res */
        $res = $qb->select('p')
            ->where($qb->expr()->eq('p.published', ':published'))
            ->orderBy('p.date', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->setParameter('published', true)
            ->getQuery()
            ->getResult()
        ;

        return new Collection($res);
    }

    public function getPost(string $alias, int $year, int $month): ?Post
    {
        $qb = $this->createQueryBuilder('p');

        /** @var Post|null */
        return $qb->select('p')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('p.published', ':published'),
                $qb->expr()->eq('p.alias', ':alias'),
                $qb->expr()->eq("DATE_EXTRACT('MONTH', p.date)", ':month'),
                $qb->expr()->eq("DATE_EXTRACT('YEAR', p.date)", ':year')
            ))
            ->setParameters(new ArrayCollection([
                new Parameter('published', true),
                new Parameter('alias', $alias),
                new Parameter('year', $year),
                new Parameter('month', $month),
            ]))
            ->getQuery()
            ->getSingleResult()
        ;
    }
}
