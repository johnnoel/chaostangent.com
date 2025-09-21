<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\DTO\PostDTO;
use App\Repository\PostRepository;
use Exception;
use Illuminate\Support\Collection;

trait GetsPosts
{
    private PostRepository $postRepository;

    private function getPostCount(mixed $alias): int
    {
        if (is_string($alias)) {
            return 1;
        }

        return $this->postRepository->countFilteredPosts(new FilterPostsCriteria());
    }

    /**
     * @return Collection<int,PostDTO>
     */
    private function getPosts(mixed $alias, int $page): Collection
    {
        if (is_string($alias)) {
            $post = $this->postRepository->findOneBy([ 'alias' => $alias ]);

            if ($post === null) {
                throw new Exception('Unable to find post with alias ' . $alias);
            }

            return new Collection([ new PostDTO($post) ]);
        }

        return $this->postRepository->filterPosts(new FilterPostsCriteria(page: $page, perPage: self::PER_PAGE));
    }
}
