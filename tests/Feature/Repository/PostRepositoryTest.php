<?php

declare(strict_types=1);

namespace App\Tests\Feature\Repository;

use App\Factory\CommentFactory;
use App\Factory\PostFactory;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\PostRepository;
use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PostRepository::class)]
class PostRepositoryTest extends WebTestCase
{
    #[DataProvider('filterPostsCommentCountProvider')]
    public function testFilterPostsCommentCount(bool $spam, bool $approved, int $expectedCount): void
    {
        static::bootKernel();
        $post = PostFactory::new()->published()->create();
        CommentFactory::createOne([
            'spam' => $spam,
            'approved' => $approved,
            'post' => $post,
        ]);

        /** @var PostRepository $postRepository */
        $postRepository = $this->getContainer()->get(PostRepository::class);
        $postDTOs = $postRepository->filterPosts(new FilterPostsCriteria());

        $this->assertCount(1, $postDTOs);
        $this->assertSame($expectedCount, $postDTOs->first()?->commentCount);
    }

    /**
     * @return array<string,mixed>
     */
    public static function filterPostsCommentCountProvider(): array
    {
        return [
            'not spam, approved' => [ false, true, 1 ],
            'not spam, not approved' => [ false, false, 0 ],
            'spam, not approved' => [ true, false, 0 ],
            'spam, approved' => [ true, true, 0 ],
        ];
    }
}
