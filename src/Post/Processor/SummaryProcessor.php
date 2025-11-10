<?php

declare(strict_types=1);

namespace App\Post\Processor;

use App\Entity\Post;

/**
 * Converts the <code><!--more--></code> blocks into content and summary fields
 */
readonly final class SummaryProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $content = $post->getContent();
        $splits = preg_split('/<!--more(.*?)?-->/', $content, 2);

        if (!is_array($splits) || count($splits) !== 2) {
            return;
        }

        [ $summary, $rest ] = $splits;

        $post->setSummary(trim($summary));
        $post->setContent(trim($summary . $rest));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'summary';
    }
}
