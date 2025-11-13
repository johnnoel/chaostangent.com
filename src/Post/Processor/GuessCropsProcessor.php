<?php

declare(strict_types=1);

namespace App\Post\Processor;

use App\Entity\Post;
use App\Image\Action\Action;
use App\Image\Action\ActionFactory;
use App\Image\Action\Crop;
use App\Image\Action\Resize;
use App\Image\Block;
use App\Image\BlockType;
use App\Image\CropGuesser;
use App\Image\Source;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

readonly final class GuessCropsProcessor implements Processor
{
    public function __construct(private ActionFactory $actionFactory)
    {
    }

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
                    new Crop($crop['w'], $crop['h'], $crop['x'], $crop['y']),
                    $this->actionFactory->createAction('resize:' . ImageType::THUMB->value),
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
        $cropAction = array_find($source->actions, fn (Action $action): bool => $action instanceof Crop);
        if ($cropAction instanceof Action) {
            return false;
        }

        // not resizing the source so don't need to crop
        $resizeAction = array_find($source->actions, fn (Action $action): bool => $action instanceof Resize);
        if ($resizeAction === null) {
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

    /**
     * @return array<Block>
     */
    private function getImageBlocks(?string $content, BlockType $blockType): array
    {
        if ($content === null) {
            return [];
        }

        $matches = [];
        $matchCount = preg_match_all($blockType->getRegex(), $content, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount === 0) {
            return [];
        }

        /** @var array<Block> $blocks */
        $blocks = [];

        for ($i = 0; $i < $matchCount; $i++) {
            $blockCrawler = new Crawler($matches[0][$i][0]);

            try {
                $sources = $this->getSources($blockCrawler);
            } catch (Exception) {
                continue;
            }

            if (count($sources) === 0) {
                continue;
            }

            $blocks[] = new Block($sources, $matches[0][$i][0], $matches[0][$i][1]);
        }

        return $blocks;
    }

    /**
     * @return array<Source>
     */
    private function getSources(Crawler $blockCrawler): array
    {
        $linksCrawler = $blockCrawler->filter('a[href^="https://chaostangent.com/media/"]');

        if ($linksCrawler->count() === 0) {
            return [];
        }

        $sources = [];
        $linksCrawler->each(function (Crawler $linkCrawler) use (&$sources): void {
            $sources[] = $this->getSource($linkCrawler);
        });

        return $sources;
    }

    private function getSource(Crawler $linkCrawler): Source
    {
        $images = $linkCrawler->filter('img');

        if ($images->count() !== 1) {
            throw new Exception('No images within the link: ' . $linkCrawler->outerHtml());
        }

        $image = $images->first();
        $imgSrc = strval($image->attr('src'));
        $caption = $image->attr('title');
        $actions = $this->getActions($imgSrc);

        // https://chaostangent.com/media/abcdef => abcdef
        $sourcePath = substr(strval(parse_url(strval($linkCrawler->attr('href')), PHP_URL_PATH)), 7);
        $imgPath = substr(strval(parse_url($imgSrc, PHP_URL_PATH)), 7);

        return new Source($sourcePath, $actions, $caption, $imgPath);
    }

    /**
     * @return array<Action>
     */
    private function getActions(string $src): array
    {
        $src = html_entity_decode($src);

        if (str_contains($src, '?')) {
            return $this->getActionsFromQueryString($src);
        }

        return $this->guessActionsFromFilename($src);
    }

    /**
     * @return array<Action>
     */
    private function getActionsFromQueryString(string $src): array
    {
        $queryString = parse_url($src, PHP_URL_QUERY);

        if (!is_string($queryString)) {
            throw new Exception('No query string on the image source: ' . $src);
        }

        $qs = [];
        parse_str($queryString, $qs);

        if (!array_key_exists('g', $qs) || !is_string($qs['g'])) {
            throw new Exception('No group found in query string: ' . $queryString);
        }

        $oldGroup = OldImageType::tryFrom($qs['g']);

        if ($oldGroup === null) {
            throw new Exception('Unknown group found: ' . var_export($qs['g'], true));
        }

        $actions = [];

        if (array_key_exists('c', $qs) && is_string($qs['c'])) {
            $actions[] = $this->actionFactory->createAction('crop:' . $qs['c']);
        }

        return array_merge($actions, [
            $this->actionFactory->createAction('resize:' . ImageType::fromOldType($oldGroup)->value),
        ]);
    }

    /**
     * @return array<Action>
     */
    private function guessActionsFromFilename(string $src): array
    {
        $regex = '#-(\d{3})x(\d{3})\..*$#';
        $matches = [];
        $matchCount = preg_match($regex, $src, $matches);

        if ($matchCount !== 1) {
            throw new Exception('No resize parameters in image src: ' . $src);
        }

        return [
            new Resize(intval($matches[1]), intval($matches[2])),
        ];
    }
}
