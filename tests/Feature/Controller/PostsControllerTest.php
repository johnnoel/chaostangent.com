<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\PostsController;
use App\Factory\PostFactory;
use App\Tests\WebTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(PostsController::class)]
class PostsControllerTest extends WebTestCase
{
    public function testPost(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $url = sprintf('/%s/%s/%s/', $post->getDate()?->format('Y'), $post->getDate()?->format('m'), $post->getAlias());

        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testPostRss(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $url = sprintf('/%s/%s/%s/', $post->getDate()?->format('Y'), $post->getDate()?->format('m'), $post->getAlias());

        $client->request('GET', $url . 'feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testPostAtom(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $url = sprintf('/%s/%s/%s/', $post->getDate()?->format('Y'), $post->getDate()?->format('m'), $post->getAlias());

        $client->request('GET', $url . 'feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    #[DataProvider('postNotFoundProvider')]
    public function testPostNotFound(string $url): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-10'),
            'alias' => 'test-post-1',
        ]);
        PostFactory::new()->create([ // unpublished
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-10'),
            'alias' => 'test-post-2',
        ]);

        $client->request('GET', '/2025/06/test-post-1/');
        $this->assertResponseIsSuccessful();

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('GET', $url . 'feed/');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('GET', $url . 'feed/atom/');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function postNotFoundProvider(): array
    {
        return [
            'incorrect alias' => [ '/2025/06/test-post-0/' ],
            'incorrect year' => [ '2024/06/test-post-1/' ],
            'incorrect month' => [ '2025/07/test-post-1/' ],
            'unpublished' => [ '2025/06/test-post-2/' ],
        ];
    }

    /**
     * @param array<string,mixed> $postData
     */
    #[DataProvider('commentProvider')]
    public function testComment(array $postData): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );
        $commentUrl = $postUrl . 'comment/';

        $client->request('POST', $commentUrl, [ 'comment' => $postData ]);

        $this->assertResponseRedirects($postUrl . '#respond');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        //$this->assertStringContainsString($postData['comment'], $client->getResponse()->getContent());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function commentProvider(): array
    {
        $default = [
            'authorName' => 'Test author',
            'authorEmail' => 'test@test.test',
            'comment' => 'Test test test test test test',
        ];

        return [
            'minimal' => [ $default ],
            'with url' => [ array_merge($default, [ 'authorUrl' => 'https://chaostangent.com' ]) ],
        ];
    }

    /**
     * @param array<string,mixed> $postData
     */
    #[DataProvider('commentFailProvider')]
    public function testCommentFail(array $postData, string $expectedMessage): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );
        $commentUrl = $postUrl . 'comment/';

        $client->request('POST', $commentUrl, [ 'comment' => $postData ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString($expectedMessage, strval($client->getResponse()->getContent()));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function commentFailProvider(): array
    {
        $default = [
            'authorName' => 'Test author',
            'authorEmail' => 'test@test.test',
            'comment' => 'Test test test test test test',
        ];

        return [
            'no author name' => [ array_diff_key($default, [ 'authorName' => null ]), 'Please enter your name' ],
            'short author name' => [
                array_merge($default, [ 'authorName' => 'A' ]),
                'Please enter at least 2 characters for your name',
            ],
            'long author name' => [
                array_merge($default, [ 'authorName' => str_repeat('a', 256) ]),
                'Please enter at most 255 characters for your name',
            ],
            'no author email' => [
                array_diff_key($default, [ 'authorEmail' => null ]),
                'Please enter your email address',
            ],
            'long author email' => [
                array_merge($default, [ 'authorEmail' => str_repeat('a', 255) . '@a.a' ]),
                'Please enter at most 255 characters for your email address',
            ],
            'invalid author email' => [
                array_merge($default, [ 'authorEmail' => '!"$£%^&*(' ]),
                'Please enter a valid email address',
            ],
            'invalid author url' => [
                array_merge($default, [ 'authorUrl' => 'ftp://test.test/test.ext' ]),
                'Please enter a URL beginning with http or https',
            ],
            'long author url' => [
                array_merge($default, [ 'authorUrl' => 'https://' . str_repeat('a', 1024) . '.com' ]),
                'Please enter at most 1024 characters for your URL',
            ],
            'no comment' => [ array_diff_key($default, [ 'comment' => null ]), 'Please enter a comment' ],
            'short comment' => [
                array_merge($default, [ 'comment' => 'Test test' ]),
                'Please enter at least 10 characters for your comment',
            ],
            'long comment' => [
                array_merge($default, [ 'comment' => str_repeat('a', 8193) ]),
                'Please enter at most 8192 characters for your comment',
            ],
        ];
    }

    #[DataProvider('commentNotFoundProvider')]
    public function testCommentNotFound(string $url): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-08-02'),
            'alias' => 'test-post-1',
        ]);
        PostFactory::new()->create([ // unpublished
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-08-02'),
            'alias' => 'test-post-2',
        ]);

        $postData = [
            'comment' => [
                'authorName' => 'Test author',
                'authorEmail' => 'test@test.test',
                'authorUrl' => 'https://chaostangent.com',
                'comment' => 'Test test test test test',
            ],
        ];

        $client->request('POST', '/2025/08/test-post-1/comment/', $postData);
        $this->assertResponseRedirects('/2025/08/test-post-1/#respond');

        $client->request('POST', $url, $postData);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function commentNotFoundProvider(): array
    {
        return [
            'incorrect alias' => [ '/2025/08/test-post-0/comment/' ],
            'incorrect year' => [ '2024/08/test-post-1/comment/' ],
            'incorrect month' => [ '2025/09/test-post-1/comment/' ],
            'unpublished' => [ '2025/08/test-post-2/comment/' ],
        ];
    }
}
