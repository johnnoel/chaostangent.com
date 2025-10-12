<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use App\Image\Action;
use App\Image\Block;
use App\Image\BlockType;
use App\Image\CropGuesser;
use App\Image\Source;
use Exception;

readonly final class GuessCropsProcessor implements Processor
{
    use ProcessesImageBlocks;

    #[\Override]
    public function process(Post $post): void
    {
        $post->setContent($this->guessCrops($post->getContent()));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'guess-crops';
    }

    /**
     * @return ($content is string ? string : null)
     */
    private function guessCrops(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        // go in reverse order so we don't have to mess with offsets when replacing
        $imageBlocks = array_reverse(array_filter(
            $this->getImageBlocks($content, BlockType::THUMBNAILS),
            fn (Block $b): bool => count($b->sources) > 0
        ));

        if (count($imageBlocks) === 0) {
            return $content;
        }

        $cropGuesser = new CropGuesser();

        foreach ($imageBlocks as $imageBlock) {
            // go through each source and decide whether its crop needs guessing
            $sources = array_filter($imageBlock->sources, [ $this, 'cropNeedsGuessing' ]);

            if (count($sources) === 0) {
                continue;
            }

            $modifiedSources = [];

            foreach ($sources as $source) {
                // devtodo remove fixed "media/" prefix
                // devtodo cache the crop against the source and variant
                $crop = $cropGuesser->guessCrop('media/' . $source->src, 'media/' . $source->variant);
                $modifiedSources[] = new Source($source->src, [
                    new Action('crop', sprintf('%dx%d+%d+%d', $crop['w'], $crop['h'], $crop['x'], $crop['y'])),
                    new Action('resize', ImageType::THUMB->value),
                ], $source->caption);
            }

            $replacement = sprintf('{{ %s([ %s ]) }}', 'thumbnails', implode(",\n", $modifiedSources));
            $content = substr_replace($content, $replacement, $imageBlock->offset, $imageBlock->length);
        }

        return $content;
    }

    private function cropNeedsGuessing(Source $source): bool
    {
        // can't guess if we don't have a comparison
        if ($source->variant === null) {
            return false;
        }

        // the source already has a crop action
        $cropAction = array_find($source->actions, fn (Action $action): bool => $action->action === 'crop');
        if ($cropAction instanceof Action) {
            return false;
        }

        // not resizing the source so don't need to crop
        $resizeAction = array_find($source->actions, fn (Action $action): bool => $action->action === 'resize');
        if ($resizeAction === null) {
            return false;
        }

        try {
            // resize parameter isn't in the width x height format - usually an image type name e.g. thumb
            $resizeAction->getResizeParameters();
        } catch (Exception) {
            return false;
        }

        // devtodo remove fixed "media/" prefix
        $sourceSize = getimagesize('media/' . $source->src);
        $variantSize = getimagesize('media/' . $source->variant);

        if ($sourceSize !== false && $variantSize !== false) {
            $sourceRatio = $sourceSize[0] / $sourceSize[1];
            $variantRatio = $variantSize[0] / $variantSize[1];

            // don't crop if the ratio of the thumbnail is the same as the source
            if (abs($sourceRatio - $variantRatio) <= 0.1) {
                return false;
            }
        }

        return true;
    }
}
