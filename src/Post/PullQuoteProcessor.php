<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;

/**
 * Converts <code><!--pullquote1--></code> into proper blockquotes rather than pulling from the extra array
 */
readonly final class PullQuoteProcessor implements Processor
{
    public function process(Post $post): void
    {
        $content = $post->getContent();
        $matches = [];
        $matched = preg_match_all('/<!--\s*(pullquote\d+)\s*-->/', $content, $matches);

        if ($matched === 0 || $matched === false) {
            return;
        }

        $extra = $post->getExtra();
        foreach ($matches[1] as $k => $match) {
            if (!array_key_exists($match, $extra) || !is_string($extra[$match])) {
                continue;
            }

            $replace = sprintf('<blockquote class="pullquote">%s</blockquote>', $extra[$match]);
            $content = str_replace($matches[0][$k], $replace, $content);
            unset($extra[$match]);
        }

        $post->setContent($content);
        $post->setExtra($extra);
    }

    public function getSlug(): string
    {
        return 'pullquote';
    }
}
