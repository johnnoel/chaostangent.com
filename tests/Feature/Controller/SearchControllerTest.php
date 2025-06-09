<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testSearch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/search');

        $this->assertResponseIsSuccessful();
    }

    public function testOpenSearch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/opensearch.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('xml');
    }
}
