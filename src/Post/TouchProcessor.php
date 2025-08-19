<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;

readonly final class TouchProcessor implements Processor
{
    public function process(Post $post): void
    {
        $post->setContent($post->getContent());
    }

    public function getSlug(): string
    {
        return 'touch';
    }
}
