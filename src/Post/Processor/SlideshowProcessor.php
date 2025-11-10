<?php

declare(strict_types=1);

namespace App\Post\Processor;

use App\Entity\Post;

readonly final class SlideshowProcessor extends ImageBlockProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $post->setContent($this->processImageBlocks($post->getContent()));
        $post->setSummary($this->processImageBlocks($post->getSummary()));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'slideshow';
    }

    #[\Override]
    protected function getBlockRegex(): string
    {
        return '#<p class="slideshow">(.*?)</p>#s';
    }

    #[\Override]
    protected function getTwigFunctionName(): string
    {
        return 'slideshow';
    }
}
