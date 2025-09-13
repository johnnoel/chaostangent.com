<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;

readonly final class ThumbnailsProcessor extends ImageBlockProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $post->setContent(strval($this->processImageBlocks($post->getContent())));
        $post->setSummary($this->processImageBlocks($post->getSummary()));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'thumbnails';
    }

    #[\Override]
    protected function getBlockRegex(): string
    {
        return '#<p class="thumbnails.*?">(.*?)</p>#s';
    }

    #[\Override]
    protected function getTwigFunctionName(): string
    {
        return 'thumbnails';
    }
}
