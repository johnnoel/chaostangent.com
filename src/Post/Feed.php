<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;

readonly final class Feed
{
    /**
     * @param array<Post>|Post $items
     */
    public function __construct(public array|Post $items)
    {
    }
}
