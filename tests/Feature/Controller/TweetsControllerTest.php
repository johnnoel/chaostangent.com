<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\TweetsController;
use App\Factory\TweetFactory;
use App\Repository\TweetRepository;
use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TweetsController::class)]
class TweetsControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        TweetFactory::new()->many(10)->create();

        $client->request('GET', '/tweets');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('Last-Modified');
        $this->assertResponseHasHeader('ETag');
    }

    public function testIndexNoTweets(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tweets');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testIndexPagination(): void
    {
        $client = static::createClient();
        TweetFactory::new()->many(31)->create();

        /** @var TweetRepository $tweetRepository */
        $tweetRepository = static::getContainer()->get(TweetRepository::class);
        $page1Tweets = $tweetRepository->findBy([], orderBy: [ 'createdAt' => 'DESC' ], limit: 30);
        $page2Tweets = $tweetRepository->findBy([], orderBy: [ 'createdAt' => 'DESC' ], limit: 30, offset: 30);

        $client->request('GET', '/tweets/page/2');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            $page2Tweets[0]->getFullText(),
            strval($client->getResponse()->getContent())
        );
        $this->assertStringNotContainsString(
            $page1Tweets[29]->getFullText(),
            strval($client->getResponse()->getContent())
        );
    }

    public function testTweet(): void
    {
        $client = static::createClient();
        $tweet = TweetFactory::createOne();

        $client->request('GET', '/tweets/' . $tweet->getId());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($tweet->getFullText(), strval($client->getResponse()->getContent()));
        $this->assertResponseHasHeader('Last-Modified');
        $this->assertResponseHasHeader('ETag');
    }
}
