<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Tag;
use App\Repository\DTO\TagDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function createMany(Tag ...$tags): void
    {
        array_walk($tags, fn (Tag $t) => $this->getEntityManager()->persist($t));
        $this->getEntityManager()->flush();
    }

    public function update(Tag $tag): void
    {
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
    }

    public function delete(Tag $tag): void
    {
        $this->getEntityManager()->remove($tag);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Collection<int,TagDTO>
     */
    public function getTagsWithMostPosts(int $limit = 25): Collection
    {
        $qb = $this->createQueryBuilder('t');
        $qb->addSelect([ 'COUNT(p.id) AS post_count' ])
            ->leftJoin('t.posts', 'p', 'WITH', 'p.published = :published')
            ->groupBy('t.id')
            ->having($qb->expr()->gt('COUNT(p.id)', ':post_count'))
            ->orderBy('post_count', 'DESC')
            ->addOrderBy('t.tag', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('published', true)
            ->setParameter('post_count', 0)
        ;

        /** @var array<array{0: Tag, post_count: int}> $result */
        $result = $qb->getQuery()->getResult();
        $dtos = array_map(fn (array $r): TagDTO => new TagDTO($r[0], intval($r['post_count'])), $result);

        usort($dtos, fn (TagDTO $a, TagDTO $b): int => $a->tag->getTag() <=> $b->tag->getTag());

        return new Collection($dtos);
    }

    /**
     * @return Collection<int,TagDTO>
     */
    public function getTagsForPost(Post $post): Collection
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Tag::class, 't');
        $rsm->addScalarResult('post_count', 'post_count');
        $selectClause = $rsm->generateSelectClause([ 't' => 't' ]);

        $sql = <<<SQL
            SELECT $selectClause, COUNT(p2t2.post_id) AS post_count
            FROM posts p
            JOIN posts2tags p2t ON p2t.post_id = p.id
            JOIN tags t ON t.id = p2t.tag_id
            JOIN posts2tags p2t2 ON p2t2.tag_id = t.id
            WHERE (p.id = :post_id)
                AND (p.published = :published)
            GROUP BY t.id
            ORDER BY t.tag ASC
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('post_id', $post->getId(), UlidType::NAME);
        $query->setParameter('published', true);

        /** @var array<array{0: Tag, post_count: int}> $result */
        $result = $query->getResult();
        $dtos = array_map(fn (array $r): TagDTO => new TagDTO($r[0], intval($r['post_count'])), $result);

        return new Collection($dtos);
    }

    /**
     * @param array<string> $rawTags
     * @return Collection<int,Tag>
     */
    public function findOrCreate(array $rawTags): Collection
    {
        $dql = <<<DQL
            SELECT t
            FROM App\Entity\Tag t
            WHERE t.tag IN (:tags)
        DQL;

        $query = $this->getEntityManager()->createQuery($dql);
        /** @var array<Tag> $existingTags */
        $existingTags = $query->setParameter('tags', $rawTags, ArrayParameterType::STRING)
            ->getResult()
        ;

        // devtodo create the tags that don't already exist

        return new Collection($existingTags);
    }
}
