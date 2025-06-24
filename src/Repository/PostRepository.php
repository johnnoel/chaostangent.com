<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Repository\DTO\PostDTO;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
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

    /**
     * @return Collection<int,Post>
     */
    public function getPostsForCategory(Category $category, int $page, int $perPage): Collection
    {
        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            JOIN p.categories c
            WHERE (c = :category) AND (p.published = :published)
            ORDER BY p.date DESC, p.id DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Post> $result */
        $result = $query->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->setParameter('category', $category->getId(), UlidType::NAME)
            ->setParameter('published', true)
            ->getResult()
        ;

        return new Collection($result);
    }

    public function getPostCountForCategory(Category $category): int
    {
        $dql = <<<DQL
            SELECT COUNT(p) FROM App\Entity\Post p
            JOIN p.categories c
            WHERE (c = :category) AND (p.published = :published)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('category', $category->getId(), UlidType::NAME)
            ->setParameter('published', true)
        ;

        return intval($query->getSingleScalarResult());
    }

    /**
     * @return Collection<int,Post>
     */
    public function getPostsForYearAndMonth(int $year, int $month, int $page, int $perPage): Collection
    {
        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            WHERE (p.published = true)
                AND (DATE_EXTRACT('YEAR', p.date) = :year)
                AND (DATE_EXTRACT('MONTH', p.date) = :month)
            ORDER BY p.date DESC, p.id DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Post> $result */
        $result = $query->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getResult()
        ;

        return new Collection($result);
    }

    public function getPostCountForYearAndMonth(int $year, int $month): int
    {
        $dql = <<<DQL
            SELECT COUNT(p) FROM App\Entity\Post p
            WHERE (p.published = true)
                AND (DATE_EXTRACT('YEAR', p.date) = :year)
                AND (DATE_EXTRACT('MONTH', p.date) = :month)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('year', $year)
            ->setParameter('month', $month)
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

    /**
     * @return Generator<PostDTO>
     */
    public function getSitemapPosts(): Generator
    {
        $dql = <<<DQL
            SELECT COUNT(p)
            FROM App\Entity\Post p
            WHERE p.published = true
        DQL;

        $postCount = intval($this->getEntityManager()->createQuery($dql)->getSingleScalarResult());

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');
        $rsm->addScalarResult('last_modified', 'last_modified', Types::DATETIME_IMMUTABLE);
        $selectClause = $rsm->generateSelectClause([ 'p' => 'p' ]);

        $batchSize = 100;
        $sql = <<<SQL
            WITH last_modified AS (
                SELECT p.id, GREATEST(p.date, p.updated, MAX(c.created)) AS last_modified
                FROM posts p
                LEFT JOIN comments c ON (c.post_id = p.id) AND (c.approved = true)
                WHERE (p.published = true)
                GROUP BY p.id
            )
            SELECT $selectClause, last_modified.last_modified
            FROM posts p
            JOIN last_modified ON last_modified.id = p.id
            WHERE (p.published = true)
            ORDER BY p.date DESC, p.id DESC
            LIMIT $batchSize OFFSET :offset
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        for ($i = 0; $i < $postCount; $i += $batchSize) {
            /** @var array<array{0: Post, last_modified: DateTimeImmutable}> $result */
            $result = $query->setParameter('offset', $i)
                ->getResult();

            yield from array_map(fn (array $row): PostDTO => new PostDTO($row[0], $row['last_modified']), $result);
        }
    }
}
