<?php

declare(strict_types=1);

namespace App\Extension\Serializer\Rss;

use App\Entity\Comment;
use App\Post\FeedUrlGenerator;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class CommentNormaliser implements NormalizerInterface
{
    public function __construct(private FeedUrlGenerator $urlGenerator)
    {
    }

    /** @inheritdoc */
    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!($data instanceof Comment)) {
            throw new InvalidArgumentException('$data must be an instance of ' . Comment::class);
        }

        $comment = $data;

        return [
            'title' => 'By : ' . $comment->getAuthorName(),
            'link' => $this->urlGenerator->getCommentUrl($comment),
            'pubDate' => $comment->getCreated()->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s O'),
            'dc:creator' => $comment->getAuthorName(),
            'guid' => [
                '@isPermaLink' => 'false',
                '#' => $this->urlGenerator->getCommentUrl($comment),
            ],
            'description' => $comment->getComment(),
            'content:encoded' => $comment->getComment(),
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Comment && $format === 'rss';
    }

    /** @inheritdoc */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            Comment::class => true,
        ];
    }
}
