<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.post_processor')]
interface Processor
{
    public function process(Post $post): void;

    public function getSlug(): string;
}
