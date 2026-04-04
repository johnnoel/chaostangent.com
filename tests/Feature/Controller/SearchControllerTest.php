<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\SearchController;
use App\Factory\PostFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(SearchController::class)]
class SearchControllerTest extends WebTestCase
{
    public function testSearch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/search');

        $this->assertResponseIsSuccessful();
    }

    public function testSearchQuery(): void
    {
        $client = static::createClient();

        $searchablePost = PostFactory::new()->published()->create([
            'content' => 'Lorem ipsum dolor sit amet',
            'title' => 'Test one',
        ]);

        $unpublishedPost = PostFactory::createOne([
            'content' => 'Lorem ipsum dolor sit amet',
            'title' => 'Test two',
        ]);

        $client->request('GET', '/search?q=lorem+ipsum');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString($searchablePost->getTitle(), strval($client->getResponse()->getContent()));
        $this->assertStringNotContainsString(
            $unpublishedPost->getTitle(),
            strval($client->getResponse()->getContent())
        );
    }

    public function testSearchNoResults(): void
    {
        $client = static::createClient();

        $searchablePost = PostFactory::new()->published()->create([
            'content' => 'Lorem ipsum dolor sit amet',
            'title' => 'Test one',
        ]);

        $client->request('GET', '/search?q=testestest');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Your query did not return any results',
            strval($client->getResponse()->getContent())
        );
        $this->assertStringNotContainsString($searchablePost->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testOpenSearch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/opensearch.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('xml');
    }
}
