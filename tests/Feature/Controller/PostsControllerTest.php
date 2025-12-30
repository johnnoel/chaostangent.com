<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\PostsController;
use App\Factory\PostFactory;
use App\Tests\Feature\MessageHandler\ImportPostHandlerTest;
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

    public function testPostWebmention(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $url = sprintf('/%s/%s/%s/', $post->getDate()?->format('Y'), $post->getDate()?->format('m'), $post->getAlias());

        $client->request('GET', $url);

        $this->assertResponseHasHeader('Link');
        $this->assertResponseHeaderSame('Link', '<http://localhost/webmention>; rel="webmention"');

        $this->assertStringContainsString(
            '<link rel="webmention" href="http://localhost/webmention">',
            strval($client->getResponse()->getContent())
        );
    }

    public function testPostPingback(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $url = sprintf('/%s/%s/%s/', $post->getDate()?->format('Y'), $post->getDate()?->format('m'), $post->getAlias());

        $client->request('GET', $url);

        $this->assertResponseHasHeader('X-Pingback');
        $this->assertResponseHeaderSame('X-Pingback', 'http://localhost/pingback');

        $this->assertStringContainsString(
            '<link rel="pingback" href="http://localhost/pingback">',
            strval($client->getResponse()->getContent())
        );
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

    public function testImportNoApiKey(): void
    {
        $client = static::createClient();

        $client->request('POST', '/import');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testImportInvalidApiKey(): void
    {
        $client = static::createClient();

        $client->request('POST', '/import', server: [
            'HTTP_X_API_KEY' => 'invalid-api-key',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /** @see ImportPostHandlerTest */
    #[DataProvider('importProvider')]
    public function testImport(string $requestContent, int $expectedResponseCode, string $expectedResponse): void
    {
        $client = static::createClient();

        $client->request('POST', '/import', server: [
            'HTTP_X_API_KEY' => 'test-api-key', // see .env.test
        ], content: $requestContent);

        $this->assertResponseStatusCodeSame($expectedResponseCode);
        $this->assertStringContainsString($expectedResponse, strval($client->getResponse()->getContent()));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function importProvider(): array
    {
        $invalid = <<<TWIG
        {#---
        title: 'Test'
        ---#}
        Content
        TWIG;

        $valid = <<<TWIG
        {#---
        title: 'Test'
        date: '2025-11-13T13:07:00+00:00'
        created: '2025-11-13T13:07:00+00:00'
        updated: '2025-11-13T13:07:00+00:00'
        alias: 'test-post-1'
        image:
            src: one.png
            actions: []
        extra: {}
        tags: []
        categories: null
        ---#}
        Content
        TWIG;

        return [
            'nothing' => [ '', 400, 'No frontmatter found' ],
            'invalid' => [ $invalid, 400, 'Expected argument of type "DateTimeImmutable"' ],
            'valid' => [ $valid, 200, '' ],
        ];
    }
}
