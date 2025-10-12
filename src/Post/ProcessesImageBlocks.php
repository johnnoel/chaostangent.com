<?php

declare(strict_types=1);

namespace App\Post;

use App\Image\Action;
use App\Image\Block;
use App\Image\BlockType;
use App\Image\Source;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

trait ProcessesImageBlocks
{
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
            $actions[] = new Action('crop', $qs['c']);
        }

        return array_merge($actions, [
            new Action('resize', ImageType::fromOldType($oldGroup)->value),
        ]);
    }

    /**
     * @return array<Action>
     */
    private function guessActionsFromFilename(string $src): array
    {
        $regex = '#-(\d{3}x\d{3})\..*$#';
        $matches = [];
        $matchCount = preg_match($regex, $src, $matches);

        if ($matchCount !== 1) {
            throw new Exception('No resize parameters in image src: ' . $src);
        }

        return [
            new Action('resize', $matches[1]),
        ];
    }
}
