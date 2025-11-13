<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Post;

readonly final class GenerateImages
{
    public function __construct(public Post $post, public bool $force = false)
    {
    }
}
