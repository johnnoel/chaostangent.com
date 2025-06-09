<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\DefaultController;
use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultController::class)]
class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRss(): void
    {
        $client = static::createClient();

        $client->request('GET', '/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testIndexAtom(): void
    {
        $client = static::createClient();

        $client->request('GET', '/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    public function testIndexPaginated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testYear(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/');

        $this->assertResponseIsSuccessful();
    }

    public function testYearRss(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testYearAtom(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    public function testYearPaginated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testMonth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/06/');

        $this->assertResponseIsSuccessful();
    }

    public function testMonthRss(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/06/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testMonthAtom(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/06/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    public function testMonthPaginated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/2025/06/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testTag(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tag/test-tag/');

        $this->assertResponseIsSuccessful();
    }

    public function testTagRss(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tag/test-tag/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testTagAtom(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tag/test-tag/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    public function testTagPaginated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tag/test-tag/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testSitemap(): void
    {
        $client = static::createClient();

        $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('xml');
    }

    public function testAbout(): void
    {
        $client = static::createClient();

        $client->request('GET', '/about/');

        $this->assertResponseIsSuccessful();
    }
}
