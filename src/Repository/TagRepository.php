<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\Repository\DTO\TagDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
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

        return new Collection($dtos);
    }
}
