<?php

declare(strict_types=1);

namespace App\Post;

use Exception;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Convert a block of images into a Twig function call suitable for {{ thumbnails }} or {{ slideshow }}
 */
readonly abstract class ImageBlockProcessor
{
    abstract protected function getBlockRegex(): string;

    abstract protected function getTwigFunctionName(): string;

    /**
     * @return ($content is string ? string : null)
     */
    protected function processImageBlocks(?string $content): ?string
    {
        if ($content === null) {
            return $content;
        }

        $matches = [];
        $matchCount = preg_match_all($this->getBlockRegex(), $content, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount === 0) {
            return $content;
        }

        // go in reverse order so we don't have to mess with offsets when replacing
        for ($i = $matchCount - 1; $i >= 0; $i--) {
            $slideshow = new Crawler($matches[0][$i][0]);
            $sources = $this->getSources($slideshow);

            if (count($sources) === 0) {
                continue;
            }

            $replacement = sprintf(
                '{{ %s([ %s ]) }}',
                $this->getTwigFunctionName(),
                implode(",\n", $sources)
            );

            $length = strlen($matches[0][$i][0]);
            $offset = $matches[0][$i][1];
            $content = substr_replace($content, $replacement, $offset, $length);
        }

        return $content;
    }

    /**
     * @return array<string>
     */
    private function getSources(Crawler $slideshow): array
    {
        $links = $slideshow->filter('a[href^="https://chaostangent.com/media/"]');

        if ($links->count() === 0) {
            return [];
        }

        $sources = [];
        $links->each(function (Crawler $link) use (&$sources): void {
            $sources[] = $this->getImage($link);
        });

        return $sources;
    }

    private function getImage(Crawler $link): string
    {
        $images = $link->filter('img');

        if ($images->count() !== 1) {
            throw new Exception('No images within the link: ' . $link->outerHtml());
        }

        $image = $images->first();
        $imgSrc = strval($image->attr('src'));
        $queryString = parse_url(html_entity_decode($imgSrc), PHP_URL_QUERY);

        if (!is_string($queryString)) {
            throw new Exception('No query string on the image source: ' . $imgSrc);
        }

        // https://chaostangent.com/media/abcdef => abcdef
        $path = substr(strval(parse_url(strval($link->attr('href')), PHP_URL_PATH)), 7);
        $caption = $image->attr('title');
        $qs = [];
        parse_str($queryString, $qs);

        if (!array_key_exists('g', $qs) || !is_string($qs['g'])) {
            throw new Exception('No group found in query string: ' . $queryString);
        }

        $oldGroup = OldImageType::tryFrom($qs['g']);

        if ($oldGroup === null) {
            throw new Exception('Unknown group found: ' . var_export($qs['g'], true));
        }

        $actions = [
            'crop:' . ((is_string($qs['c'])) ? $qs['c'] : '0x0+0+0'),
            'resize:' . ImageType::fromOldType($oldGroup)->value,
        ];

        if (is_string($caption)) {
            return sprintf(
                "{ 'src': '%s', 'actions': [ '%s' ], 'caption': '%s' }",
                $path,
                implode("', '", $actions),
                str_replace("'", "\\'", $caption)
            );
        }

        return sprintf("{ 'src': '%s', 'actions': [ '%s' ] }", $path, implode("', '", $actions));
    }
}
