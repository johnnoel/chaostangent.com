<?php

declare(strict_types=1);

namespace App\Extension\Serializer;

use App\Entity\Comment;
use App\Post\FeedUrlGenerator;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class AtomCommentNormaliser implements NormalizerInterface
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
        $created = $comment->getCreated()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        return [
            'title' => 'By : ' . $comment->getAuthorName(),
            'link' => [
                '@href' => $this->urlGenerator->getCommentUrl($comment),
                '@rel' => 'alternate',
                '@type' => 'text/html',
            ],
            'id' => $this->urlGenerator->getCommentUrl($comment),
            'updated' => $created,
            'published' => $created,
            'content' => [
                '@type' => 'html',
                '#' => $comment->getComment(),
            ],
            'author' => [
                'name' => $comment->getAuthorName(),
                'uri' => $comment->getAuthorUrl() ?? '',
            ],
            'thr:in-reply-to' => [
                '@ref' => $this->urlGenerator->getPostUrl($comment->getPost()),
                '@href' => $this->urlGenerator->getPostUrl($comment->getPost()),
                '@type' => 'text/html',
            ],
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Comment && $format === 'atom';
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
