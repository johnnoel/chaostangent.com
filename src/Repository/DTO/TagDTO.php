<?php

declare(strict_types=1);

namespace App\Repository\DTO;

use App\Entity\Tag;

readonly final class TagDTO
{
    public function __construct(public Tag $tag, public ?int $postCount = null)
    {
    }
}
