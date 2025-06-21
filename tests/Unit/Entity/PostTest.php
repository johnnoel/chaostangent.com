<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Post;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    #[DataProvider('getFeedItemTitleProvider')]
    public function testGetFeedItemTitle(string $title, ?string $subtitle, string $expectedFeedItemTitle): void
    {
        $post = new Post($title, $subtitle, 'test-post', '', null, false, []);
        $this->assertSame($expectedFeedItemTitle, $post->getFeedItemTitle());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function getFeedItemTitleProvider(): array
    {
        return [
            'no subtitle' => [ 'Test title', null, 'Test title' ],
            'empty subtitle' => [ 'Test title', '', 'Test title' ],
            'spacey subtitle' => [ 'Test title', '    ', 'Test title' ],
            'subtitle' => [ 'Test subtitle', 'Subtitle', 'Test subtitle - Subtitle' ],
            'subtitle trailing spaces' => [ 'Test subtitle', 'Subtitle    ', 'Test subtitle - Subtitle' ],
        ];
    }
}
