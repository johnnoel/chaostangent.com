<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Collection;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return Collection<int,Category>
     */
    public function getTree(): Collection
    {
        $qb = $this->createQueryBuilder('c');
        $qb->leftJoin('c.posts', 'p', 'WITH', 'p.published = :published')
            ->groupBy('c.id')
            ->having($qb->expr()->gt('COUNT(p.id)', ':post_count'))
            ->orderBy('c.title', 'ASC')
            ->setParameter('published', true)
            ->setParameter('post_count', 0)
        ;

        /** @var array<Category> $result */
        $categories = $qb->getQuery()->getResult();
        // change the category's PersistentCollection to an ArrayCollection so that when doing getChildren(), Doctrine
        // doesn't try to fetch them from the database
        array_walk($categories, fn (Category $c) => $c->resetChildren());

        foreach ($categories as $category) {
            if ($category->getParent() === null) {
                continue;
            }

            $category->getParent()->addChild($category);
        }

        $topLevelCategories = (new Collection(
            array_filter($categories, fn (Category $c): bool => $c->getParent() === null)
        ))->sort(fn (Category $a, Category $b): int => $a->getTitle() <=> $b->getTitle());

        return $topLevelCategories;
    }
}
