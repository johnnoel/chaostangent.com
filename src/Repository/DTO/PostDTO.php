<?php

declare(strict_types=1);

namespace App\Repository\DTO;

use App\Entity\Post;
use DateTimeImmutable;

readonly final class PostDTO
{
    public DateTimeImmutable $lastModified;

    public function __construct(
        public Post $post,
        ?DateTimeImmutable $lastModified = null,
        public int $commentCount = 0
    ) {
        $this->lastModified = $lastModified ?? new DateTimeImmutable('now');
    }
}
