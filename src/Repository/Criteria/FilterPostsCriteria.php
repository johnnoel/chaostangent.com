<?php

declare(strict_types=1);

namespace App\Repository\Criteria;

use App\Entity\Category;
use App\Entity\Tag;

readonly final class FilterPostsCriteria
{
    public function __construct(
        public ?Category $category = null,
        public ?Tag $tag = null,
        public ?int $month = null,
        public ?int $year = null,
        public int $page = 1,
        public int $perPage = 5
    ) {
    }
}
