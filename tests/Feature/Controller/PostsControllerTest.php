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
            'alias' => 'test-post-1',
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
}
