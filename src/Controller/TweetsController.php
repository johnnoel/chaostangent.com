<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tweet;
use App\Repository\TweetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TweetsController extends AbstractController
{
    use CalculatesETags;

    private const int PER_PAGE = 30;

    public function __construct(
        private readonly TweetRepository $tweetRepository,
        string $assetManifestPath,
    ) {
        $this->assetManifestPath = $assetManifestPath;
    }

    #[Route('/tweets', name: 'tweets', defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    #[Route('/tweets/page/{page}', name: 'tweets:paginated', requirements: [ 'page' => '\d+' ], methods: [ 'GET' ])]
    public function index(int $page, Request $request): Response
    {
        $tweets = $this->tweetRepository->findBy(
            [],
            orderBy: [ 'createdAt' => 'DESC' ],
            limit: self::PER_PAGE,
            offset: ($page - 1) * self::PER_PAGE
        );

        if (count($tweets) === 0) {
            throw $this->createNotFoundException();
        }

        $response = (new Response())->setCache([
            'max_age' => 60 * 60,
            'public' => true,
            'must_revalidate' => true,
            'last_modified' => $tweets[0]->getCreatedAt(),
            'etag' => $this->calculateETag($tweets[0]->getCreatedAt()),
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $total = $this->tweetRepository->count();

        return $this->render('tweets/index.html.twig', [
            'tweets' => $tweets,
            'page' => $page,
            'page_count' => ceil($total / self::PER_PAGE),
        ], $response);
    }

    #[Route('/tweets/{id}', name: 'tweet', requirements: [ 'id' => '\d+' ], methods: [ 'GET' ])]
    public function tweet(Tweet $tweet, Request $request): Response
    {
        $response = (new Response())->setCache([
            'max_age' => 60 * 60,
            'public' => true,
            'must_revalidate' => true,
            'last_modified' => $tweet->getCreatedAt(),
            'etag' => $this->calculateETag($tweet->getCreatedAt()),
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render('tweets/tweet.html.twig', [
            'tweet' => $tweet,
        ], $response);
    }
}
