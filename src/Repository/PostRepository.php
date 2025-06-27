<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\DTO\PostDTO;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\QueryBuilder;
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
    public function filterPosts(FilterPostsCriteria $criteria): Collection
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where($qb->expr()->eq('p.published', ':published'))
            ->orderBy('p.date', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($criteria->perPage)
            ->setFirstResult(($criteria->page - 1) * $criteria->perPage)
            ->setParameter('published', true)
        ;

        $this->applyCriteria($criteria, $qb);

        /** @var array<Post> $result */
        $result = $qb->getQuery()->getResult();

        return new Collection($result);
    }

    public function countFilteredPosts(FilterPostsCriteria $criteria): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('COUNT(p)')
            ->where($qb->expr()->eq('p.published', ':published'))
            ->setParameter('published', true)
        ;

        $this->applyCriteria($criteria, $qb);

        return intval($qb->getQuery()->getSingleScalarResult());
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

    private function applyCriteria(FilterPostsCriteria $criteria, QueryBuilder $qb): void
    {
        if ($criteria->category !== null) {
            $qb->join('p.categories', 'c')
                ->andWhere($qb->expr()->eq('c', ':category'))
                ->setParameter('category', $criteria->category->getId(), UlidType::NAME)
            ;
        }

        if ($criteria->tag !== null) {
            $qb->join('p.tags', 't')
                ->andWhere($qb->expr()->eq('t', ':tag'))
                ->setParameter('tag', $criteria->tag->getId(), UlidType::NAME)
            ;
        }

        if ($criteria->month !== null) {
            $qb->andWhere($qb->expr()->eq("DATE_EXTRACT('MONTH', p.date)", ':month'))
                ->setParameter('month', $criteria->month)
            ;
        }

        if ($criteria->year !== null) {
            $qb->andWhere($qb->expr()->eq("DATE_EXTRACT('YEAR', p.date)", ':year'))
                ->setParameter('year', $criteria->year)
            ;
        }
    }
}
