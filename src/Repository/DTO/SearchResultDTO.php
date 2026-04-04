<?php

declare(strict_types=1);

namespace App\Repository\DTO;

use App\Entity\Post;

readonly final class SearchResultDTO
{
    public function __construct(public Post $post, public float $score)
    {
    }
}
