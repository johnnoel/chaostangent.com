<?php

declare(strict_types=1);

namespace App\Tests\Feature\MessageHandler;

use App\Factory\PostFactory;
use App\Message\ProcessIncomingWebmention;
use App\MessageHandler\ProcessIncomingWebmentionHandler;
use App\Repository\PostRepository;
use App\Repository\WebmentionRepository;
use App\Tests\WebTestCase;
use ColinODell\PsrTestLogger\TestLogger;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProcessIncomingWebmentionHandlerTest extends WebTestCase
{
    public function testNoPath(): void
    {
        static::bootKernel();

        $logger = new TestLogger();
        $handler = $this->getHandler($logger);

        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains('No path in URL https://target.test'));
    }

    public function testInvalidHost(): void
    {
        static::bootKernel();

        $logger = new TestLogger();
        $handler = $this->getHandler($logger);

        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://invalid.target.test/',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains('Invalid host https://invalid.target.test'));
    }

    public function testBadRoute(): void
    {
        static::bootKernel();

        $logger = new TestLogger();
        $handler = $this->getHandler($logger);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/a/b/c/d/e/f/g',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains('Unable to match target to a route'));
    }

    public function testNotAllowedRoute(): void
    {
        static::bootKernel();

        $logger = new TestLogger();
        $handler = $this->getHandler($logger);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/about/',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains('No route parameter in matched route'));
    }

    public function testSource404s(): void
    {
        static::bootKernel();

        $httpClient = new MockHttpClient([ new MockResponse('Not found', [ 'http_code' => 404 ]) ]);
        $logger = new TestLogger();
        $handler = $this->getHandler($logger, $httpClient);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/2025/12/test-post',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains('Status code was not 200 - 404'));
    }

    public function testSourceDoesNotContainTarget(): void
    {
        static::bootKernel();

        $httpClient = new MockHttpClient([
            new MockResponse('https://target.test/2025/12/different-post', [ 'http_code' => 200 ]),
        ]);
        $logger = new TestLogger();
        $handler = $this->getHandler($logger, $httpClient);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/2025/12/test-post',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains(
            'https://source.test/ does not contain target https://target.test/2025/12/test-post'
        ));
    }

    public function testTargetPostDoesntExist(): void
    {
        static::bootKernel();

        $httpClient = new MockHttpClient([
            new MockResponse('https://target.test/2025/12/test-post', [ 'http_code' => 200 ]),
        ]);
        $logger = new TestLogger();
        $handler = $this->getHandler($logger, $httpClient);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/2025/12/test-post',
            '127.0.0.1',
        ));

        $this->assertTrue($logger->hasDebugRecords());
        $this->assertTrue($logger->hasDebugThatContains(
            'Unable to find post with parameters alias: test-post, year: 2025, month: 12'
        ));
    }

    public function testSuccess(): void
    {
        static::bootKernel();

        $post = PostFactory::new()->published()->create([
            'alias' => 'test-post',
            'date' => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-12-26 01:23:45'),
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse('https://target.test/2025/12/test-post', [ 'http_code' => 200 ]),
        ]);
        $logger = new TestLogger();

        $this->assertCount(0, $post->getWebmentions());

        $handler = $this->getHandler($logger, $httpClient);
        $handler(new ProcessIncomingWebmention(
            'https://source.test/',
            'https://target.test/2025/12/test-post',
            '127.0.0.1',
        ));

        $this->assertFalse($logger->hasDebugRecords());
        $this->assertCount(1, $post->getWebmentions());
    }

    private function getHandler(
        LoggerInterface $logger = new TestLogger(),
        HttpClientInterface $httpClient = new MockHttpClient(),
    ): ProcessIncomingWebmentionHandler {
        /** @var WebmentionRepository $webmentionRepo */
        $webmentionRepo = $this->getContainer()->get(WebmentionRepository::class);
        /** @var PostRepository $postRepository */
        $postRepository = $this->getContainer()->get(PostRepository::class);
        /** @var UrlMatcherInterface $urlMatcher */
        $urlMatcher = $this->getContainer()->get(UrlMatcherInterface::class);

        return new ProcessIncomingWebmentionHandler(
            $webmentionRepo,
            $postRepository,
            $urlMatcher,
            $httpClient,
            $logger,
            [ 'target.test' ],
        );
    }
}
