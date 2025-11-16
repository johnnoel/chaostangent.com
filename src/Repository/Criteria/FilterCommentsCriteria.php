<?php

declare(strict_types=1);

namespace App\Repository\Criteria;

use App\Entity\Post;

readonly final class FilterCommentsCriteria
{
    public function __construct(
        public ?Post $post = null,
        public ?bool $approved = null,
        public ?bool $spam = null,
        public int $page = 1,
        public int $perPage = 10
    ) {
    }
}
