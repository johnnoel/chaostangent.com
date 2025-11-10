<?php

declare(strict_types=1);

namespace App\Post\Processor;

use App\Entity\Post;

/**
 * Convert a post's "image" into its own database field
 */
readonly final class PostImageProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $extra = $post->getExtra();

        if (!array_key_exists('image', $extra)) {
            return;
        }

        /** @var string $oldImage */
        $oldImage = $extra['image'];

        if (!str_starts_with($oldImage, '/i/')) {
            return;
        }

        $qs = [];
        $queryString = parse_url($oldImage, PHP_URL_QUERY);
        parse_str(strval($queryString), $qs);

        if (!array_key_exists('g', $qs) || !array_key_exists('c', $qs)) {
            return;
        }

        $post->setImage([
            'src' => substr(strval(parse_url($oldImage, PHP_URL_PATH)), strlen('/i/')),
            'actions' => [
                'crop:' . (is_string($qs['c']) ? $qs['c'] : '0x0+0+0'),
                'resize:square',
            ],
        ]);
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'post-image';
    }
}
