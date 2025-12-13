<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Tweet;
use App\Message\ImportTweet;
use App\Repository\TweetRepository;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** @phpstan-type RawTweet array{tweet: array{created_at: string, id_str: string, full_text: string}} */
#[AsMessageHandler]
readonly final class ImportTweetHandler
{
    public function __construct(private TweetRepository $tweetRepository)
    {
    }

    public function __invoke(ImportTweet $message): void
    {
        /** @var RawTweet $tweetData */
        $tweetData = $message->tweet;
        // todo validate

        $createdAt = DateTimeImmutable::createFromFormat('D M d H:i:s O Y', $tweetData['tweet']['created_at']);

        if (!($createdAt instanceof DateTimeImmutable)) {
            throw new Exception('Unable to parse created_at date ' . $tweetData['tweet']['created_at']);
        }

        $tweet = new Tweet(
            strval($tweetData['tweet']['id_str']),
            $createdAt,
            strval($tweetData['tweet']['full_text']),
            '_ceetea',
            $tweetData
        );

        $this->tweetRepository->create($tweet);
    }
}
