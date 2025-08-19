<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Twig\Environment;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Post::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Post::class)]
readonly final class PostListener
{
    public function __construct(private Environment $twig)
    {
    }

    public function preUpdate(Post $post, PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField('content')) {
            return;
        }

        $this->populateSearchable($post);
    }

    public function prePersist(Post $post, PrePersistEventArgs $args): void
    {
        $this->populateSearchable($post);
    }

    public function populateSearchable(Post $post): void
    {
        $template = $this->twig->createTemplate($post->getContent(), $post->getAlias());
        $content = $template->render();

        // devtodo Add tags, title, and subtitle to this
        // devtodo Trim any double spaces
        $post->setSearchable(trim(strip_tags($content)));
    }
}
