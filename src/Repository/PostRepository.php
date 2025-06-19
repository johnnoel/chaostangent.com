<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;
use Symfony\Bridge\Doctrine\Types\UlidType;

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
        $dql = <<<DQL
            SELECT p FROM  App\Entity\Post p
            WHERE (p.published = :published)
            ORDER BY p.date DESC, p.id DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Post> $result */
        $result = $query->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->setParameter('published', true)
            ->getResult()
        ;

        return new Collection($result);
    }

    /**
     * @return Collection<int,Post>
     */
    public function getPostsForTag(Tag $tag, int $page, int $perPage): Collection
    {
        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            JOIN p.tags t
            WHERE (t = :tag) AND (p.published = :published)
            ORDER BY p.date DESC, p.id DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Post> $result */
        $result = $query->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->setParameter('tag', $tag->getId(), UlidType::NAME)
            ->setParameter('published', true)
            ->getResult()
        ;

        return new Collection($result);
    }

    public function getPostCountForTag(Tag $tag): int
    {
        $dql = <<<DQL
            SELECT COUNT(p) FROM App\Entity\Post p
            JOIN p.tags t
            WHERE (t = :tag) AND (p.published = :published)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('tag', $tag->getId(), UlidType::NAME)
            ->setParameter('published', true)
        ;

        return intval($query->getSingleScalarResult());
    }

    public function getPost(string $alias, int $year, int $month): ?Post
    {
        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            WHERE (p.published = :published)
                AND (p.alias = :alias)
                AND (DATE_EXTRACT('MONTH', p.date) = :month)
                AND (DATE_EXTRACT('YEAR', p.date) = :year)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);

        /** @var Post|null */
        return $query->setMaxResults(1)
            ->setParameter('published', true)
            ->setParameter('alias', $alias)
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->getSingleResult()
        ;
    }
}
