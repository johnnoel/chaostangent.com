<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
