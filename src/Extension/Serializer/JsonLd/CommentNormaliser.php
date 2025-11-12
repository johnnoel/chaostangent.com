<?php

declare(strict_types=1);

namespace App\Extension\Serializer\JsonLd;

use App\Entity\Comment;
use App\Post\FeedUrlGenerator;
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
            '@type' => 'Comment',
            '@id' => $this->urlGenerator->getCommentUrl($comment),
            'dateCreated' => $comment->getCreated()->format('Y-m-d\\TH:i:sP'),
            'description' => $comment->getComment(),
            'author' => [
                '@type' => 'Person',
                'name' => $comment->getAuthorName(),
                'url' => $comment->getAuthorUrl(),
            ],
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Comment && $format === Encoder::FORMAT;
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
