<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\DTO\PostDTO;
use App\Repository\DTO\SearchResultDTO;
use App\Repository\Query\FilterPostsQuery;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Illuminate\Support\Collection;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function update(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Collection<int,PostDTO>
     */
    public function filterPosts(FilterPostsCriteria $criteria): Collection
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');
        $rsm->addScalarResult('comment_count', 1, Types::INTEGER);
        $selectClause = $rsm->generateSelectClause([ 'p' => 'p' ]);

        $filterPostsQuery = new FilterPostsQuery($criteria);
        [ $sql, $parameters ] = $filterPostsQuery->posts($selectClause);

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameters($parameters); // @phpstan-ignore argument.type

        /** @var array<array{0: Post, 1: int}> $dbResult */
        $dbResult = $query->getResult();

        /** @var array<PostDTO> $result */
        $result = array_map(
            fn (array $r): PostDTO => new PostDTO(post: $r[0], commentCount: $r[1]),
            $dbResult
        );

        return new Collection($result);
    }

    public function countFilteredPosts(FilterPostsCriteria $criteria): int
    {
        $filterPostsQuery = new FilterPostsQuery($criteria);
        [ $sql, $parameters ] = $filterPostsQuery->count();

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('post_count', 0, Types::INTEGER);
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameters($parameters); // @phpstan-ignore argument.type

        return intval($query->getSingleScalarResult());
    }

    public function getPost(string $alias, int $year, int $month, ?bool $published = true): ?PostDTO
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');
        $rsm->addScalarResult('last_modified', 'last_modified', Types::DATETIME_IMMUTABLE);
        $selectClause = $rsm->generateSelectClause([ 'p' => 'p' ]);

        $params = [
            'alias' => $alias,
            'year' => $year,
            'month' => $month,
        ];

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
            WHERE (p.alias = :alias)
                AND (EXTRACT('YEAR' FROM p.date) = :year)
                AND (EXTRACT('MONTH' FROM p.date) = :month)
        SQL;

        if ($published !== null) {
            $sql .= ' AND (p.published = :published)';
            $params['published'] = $published;
        }

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        /** @var array{0: Post, last_modified: DateTimeImmutable}|null $result */
        $result = $query->setParameters($params)->getOneOrNullResult();

        if ($result === null) {
            return null;
        }

        return new PostDTO($result[0], $result['last_modified']);
    }

    /**
     * @return array{prev: ?Post, next: ?Post}
     */
    public function getSurroundingPosts(Post $post): array
    {
        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            WHERE (p.date > :date)
                AND (p.published = :published)
            ORDER BY p.date ASC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var Post|null $next */
        $next = $query->setMaxResults(1)
            ->setParameter('published', true)
            ->setParameter('date', $post->getDate())
            ->getOneOrNullResult()
        ;

        $dql = <<<DQL
            SELECT p FROM App\Entity\Post p
            WHERE (p.date < :date)
                AND (p.published = :published)
            ORDER BY p.date DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var Post|null $prev */
        $prev = $query->setMaxResults(1)
            ->setParameter('published', true)
            ->setParameter('date', $post->getDate())
            ->getOneOrNullResult()
        ;

        return [ 'prev' => $prev, 'next' => $next ];
    }

    /**
     * @return Collection<int,SearchResultDTO>
     */
    public function searchPosts(string $searchQuery, int $page, int $perPage = 10): Collection
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');
        $rsm->addScalarResult('score', 'score', 'float');
        $selectClause = $rsm->generateSelectClause([ 'p' => 'p' ]);

        $sql = <<<SQL
            SELECT $selectClause, p.searchable <@> to_bm25query(:query, 'posts_search_idx') AS score
            FROM posts p
            WHERE (p.published = true)
            ORDER BY score
            LIMIT :limit OFFSET :offset
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        /** @var array<array{0: Post, score: float}> $result */
        $result = $query->setParameter('query', $searchQuery)
            ->setParameter('limit', $perPage)
            ->setParameter('offset', ($page - 1) * $perPage)
            ->getResult()
        ;

        return new Collection(array_map(
            fn (array $r): SearchResultDTO => new SearchResultDTO($r[0], $r['score']),
            $result
        ));
    }

    public function countSearchedPosts(string $searchQuery): int
    {
        $sql = <<<SQL
            SELECT COUNT(p.id) AS post_count
            FROM posts p
            WHERE (p.searchable <@> to_bm25query(:query, 'posts_search_idx') < -0.5)
                AND (p.published = true)
        SQL;

        $result = $this->getEntityManager()->getConnection()->executeQuery(
            $sql,
            [ 'query' => $searchQuery ]
        )->fetchNumeric();

        return (is_array($result) && count($result) > 0 && is_int($result[0])) ? $result[0] : 0;
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

            yield from array_map(
                fn (array $row): PostDTO => new PostDTO($row[0], lastModified: $row['last_modified']),
                $result
            );
        }
    }

    /**
     * @return array<int,array<int>>
     */
    public function getPostCalendar(): array
    {
        $dql = <<<DQL
            SELECT DISTINCT DATE_EXTRACT('YEAR', p.date) AS year, DATE_EXTRACT('MONTH', p.date) AS month
            FROM App\Entity\Post p
            WHERE p.published = true
            ORDER BY year DESC, month ASC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<array{year: string, month: string}> $result */
        $result = $query->getResult();
        $ret = [];

        foreach ($result as $row) {
            $ret[intval($row['year'])][] = intval($row['month']);
        }

        return $ret;
    }

    /**
     * @return array<numeric-string,non-empty-array<int,int>>
     */
    public function getPostCountByWeek(): array
    {
        // use generate series to create a complete week-by-week count of posts
        // cribbed from https://www.citusdata.com/blog/2018/03/14/fun-with-sql-generate-sql/
        $sql = <<<SQL
            WITH range_values AS (
                SELECT date_trunc('week', min(date)) AS min_val, date_trunc('week', max(date)) AS max_val
                FROM posts
                WHERE published = true
            ), week_range AS (
                SELECT generate_series(min_val, max_val, 'P1W'::interval) AS week
                FROM range_values
            ), weekly_counts AS (
                SELECT date_trunc('week', date) AS week, COUNT(*) AS pc
                FROM posts
                GROUP BY 1
            )
            SELECT week_range.week, COALESCE(weekly_counts.pc, 0) AS post_count
            FROM week_range
            LEFT OUTER JOIN weekly_counts ON week_range.week = weekly_counts.week
            ORDER BY week_range.week ASC
        SQL;

        /** @var array<array{week: string, post_count: int}> $rows */
        $rows = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchAllAssociative();

        if (count($rows) === 0) {
            return [];
        }

        $format = $this->getEntityManager()->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $min = DateTimeImmutable::createFromFormat($format, $rows[0]['week']);
        $max = DateTimeImmutable::createFromFormat($format, end($rows)['week']);

        if ($min === false || $max === false) {
            return [];
        }

        $calendar = [];

        // create a [ year => [ week => post count ] ] array
        foreach ($rows as $row) {
            $date = DateTimeImmutable::createFromFormat($format, $row['week']);

            if ($date === false) {
                continue;
            }

            $calendar[$date->format('o')][intval($date->format('W'))] = intval($row['post_count']);
        }

        // todo get postgres to do this busy work
        // prepend weeks to fill out the first year
        for ($week = 1; $week < intval($min->format('W')); $week++) {
            $calendar[$min->format('o')][$week] = 0;
        }

        // append weeks to fill out the last year
        $lastMaxWeek = $max->setDate(intval($max->format('Y')), 12, 28)->format('W');
        for ($week = intval($max->format('W')); $week <= $lastMaxWeek; $week++) {
            $calendar[$max->format('o')][$week] = 0;
        }

        // ensure keys are in order for correct iteration
        array_walk($calendar, fn (array &$counts) => ksort($counts, SORT_NUMERIC));
        ksort($calendar, SORT_NUMERIC);

        return $calendar;
    }

    /**
     * @param Collection<int,Ulid> $postIds Find top posts from within these IDs
     * @return Collection<int,PostDTO>
     */
    public function findTopPosts(int $count = 20, Collection $postIds = new Collection()): Collection
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');
        $rsm->addScalarResult('comment_count', 'comment_count');
        $rsm->addScalarResult('kudo_count', 'kudo_count');
        $rsm->addScalarResult('score', 'score');
        $selectClause = $rsm->generateSelectClause([ 'p' => 'p' ]);

        $whereAnd = '';
        if ($postIds->isNotEmpty()) {
            $whereAnd = ' AND (p.id IN (:ids))';
        }

        $sql = <<<SQL
            WITH cc AS (
                SELECT p.id, COUNT(c.id) AS comment_count FROM posts p
                    LEFT JOIN comments c ON c.post_id = p.id
                    WHERE c.approved = :approved AND c.spam = :spam
                    GROUP BY p.id
            ), kc AS (
                SELECT p.id, COUNT(k.id) AS kudo_count FROM posts p
                    LEFT JOIN kudos k ON k.post_id = p.id
                    GROUP BY p.id
            ) SELECT $selectClause,
                cc.comment_count,
                kc.kudo_count,
                COALESCE(((cc.comment_count * 2) + kc.kudo_count), 0) AS score
            FROM posts p
            LEFT JOIN cc ON cc.id = p.id
            LEFT JOIN kc ON kc.id = p.id
            WHERE (p.published = :published) $whereAnd
            ORDER BY score DESC
            LIMIT :limit
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('approved', true)
            ->setParameter('spam', false)
            ->setParameter('published', true)
            ->setParameter('limit', $count)
        ;

        if ($postIds->isNotEmpty()) {
            $query->setParameter(
                'ids',
                // ULIDs are represented in RFC4122 format when using UlidType's so need to pre-convert them
                $postIds->map(fn (Ulid $id): string => $id->toRfc4122())->all(),
                ArrayParameterType::STRING
            );
        }

        /** @var array<array{0: Post, comment_count: int, kudo_count: int, score: int}> $res */
        $res = $query->getResult();
        $dtos = array_map(function (array $r): PostDTO {
            return new PostDTO($r[0], commentCount: intval($r['comment_count']), kudoCount: intval($r['kudo_count']));
        }, $res);

        return new Collection($dtos);
    }
}
