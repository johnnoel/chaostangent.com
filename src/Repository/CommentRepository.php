<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function create(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    public function update(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Collection<int,Comment>
     */
    public function getTree(Post $post): Collection
    {
        $dql = <<<DQL
            SELECT c
            FROM App\Entity\Comment c
            WHERE (c.post = :post) AND
                (c.spam = :spam) AND
                (c.approved = :approved)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Comment> $comments */
        $comments = $query->setParameter('post', $post->getId(), UlidType::NAME)
            ->setParameter('spam', false)
            ->setParameter('approved', true)
            ->getResult()
        ;

        // change the comment's PersistentCollection to an ArrayCollection so that when doing getChildren(), Doctrine
        // doesn't try to fetch them from the database
        array_walk($comments, fn (Comment $c) => $c->resetChildren());

        foreach ($comments as $comment) {
            if ($comment->getParent() === null) {
                continue;
            }

            $comment->getParent()->addChild($comment);
        }

        return new Collection(
            array_filter($comments, fn (Comment $c): bool => $c->getParent() === null)
        );
    }

    public function countComments(): int
    {
        $dql = <<<DQL
            SELECT COUNT(c.id)
            FROM App\Entity\Comment c
            WHERE (c.spam = :spam) AND
                (c.approved = :approved)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);

        try {
            return intval(
                $query->setParameter('spam', false)
                    ->setParameter('approved', true)
                    ->getSingleScalarResult()
            );
        } catch (NoResultException) {
        }

        return 0;
    }

    public function countParticipants(): int
    {
        $dql = <<<DQL
            SELECT COUNT(DISTINCT c.authorEmail)
            FROM App\Entity\Comment c
            WHERE (c.spam = :spam) AND
                (c.approved = :approved)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);

        try {
            return intval(
                $query->setParameter('spam', false)
                    ->setParameter('approved', true)
                    ->getSingleScalarResult()
            );
        } catch (NoResultException) {
        }

        return 0;
    }

    /**
     * @return array<array{0: Comment, comment_count: int}>
     */
    public function findMostParticipated(int $count = 10): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Comment::class, 'c');
        $rsm->addScalarResult('comment_count', 'comment_count');

        $selectClause = $rsm->generateSelectClause([ 'c' => 'c' ]);

        $sql = <<<SQL
            SELECT
                DISTINCT ON (c.author_email, comment_count)
                $selectClause,
                COUNT(c.id) OVER (PARTITION BY c.author_email) AS comment_count
            FROM comments c
            WHERE (c.approved = :approved)
                AND (c.spam = :spam)
            ORDER BY comment_count DESC,
                author_email ASC
            LIMIT :limit
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        /** @var array<array{0: Comment, comment_count: int}> $rows */
        $rows = $query->setParameter('approved', true)
            ->setParameter('spam', false)
            ->setParameter('limit', $count)
            ->getResult()
        ;

        return $rows;
    }

    /**
     * @return array<array{0: Comment, comment_length: int}>
     */
    public function findLongestComments(int $count = 10): array
    {
        $dql = <<<DQL
            SELECT c, LENGTH(c.comment) AS comment_length
            FROM App\Entity\Comment c
            JOIN c.post p
            WHERE (c.approved = :approved)
                AND (c.spam = :spam)
            ORDER BY comment_length DESC
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<array{0: Comment, comment_length: int}> $rows */
        $rows = $query->setParameter('approved', true)
            ->setParameter('spam', false)
            ->setMaxResults($count)
            ->getResult()
        ;

        return $rows;
    }

    /**
     * @return array<numeric-string,non-empty-array<int,int>>
     */
    public function getCommentCountByWeek(): array
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
                SELECT date_trunc('week', created) AS week, COUNT(*) AS cc
                FROM comments
                WHERE comments.spam = false AND comments.approved = true
                GROUP BY 1
            )
            SELECT week_range.week, COALESCE (weekly_counts.cc, 0) AS comment_count
            FROM week_range
            LEFT OUTER JOIN weekly_counts ON week_range.week = weekly_counts.week
            ORDER BY week_range.week ASC
        SQL;

        /** @var array<array{week: string, comment_count: int}> $rows */
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

            $calendar[$date->format('o')][intval($date->format('W'))] = intval($row['comment_count']);
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
}
