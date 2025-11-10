<?php

declare(strict_types=1);

namespace App\Post\Processor;

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

            try {
                $sources = $this->getSources($slideshow);
            } catch (Exception) {
                continue;
            }

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

        // https://chaostangent.com/media/abcdef => abcdef
        $path = substr(strval(parse_url(strval($link->attr('href')), PHP_URL_PATH)), 7);

        $image = $images->first();
        $imgSrc = strval($image->attr('src'));
        $caption = $image->attr('title');
        $actions = $this->getActions($imgSrc);

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

    /**
     * @return array<string>
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
     * @return array<string>
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

        $oldGroup = OldImageType::tryFrom($qs['g']);

        if ($oldGroup === null) {
            throw new Exception('Unknown group found: ' . var_export($qs['g'], true));
        }

        $ret = [];

        if (array_key_exists('c', $qs) && is_string($qs['c'])) {
            $ret[] = 'crop:' . $qs['c'];
        }

        return array_merge($ret, [
            'resize:' . ImageType::fromOldType($oldGroup)->value,
        ]);
    }

    /**
     * @return array<string>
     */
    private function guessActionsFromFilename(string $src): array
    {
        $regex = '#-((\d{3})x(\d{3}))\..*$#';
        $matches = [];
        $matchCount = preg_match($regex, $src, $matches);

        if ($matchCount !== 1) {
            throw new Exception('No resize parameters in image src: ' . $src);
        }

        $params = $matches[1];

        // work out if we can change the resize parameters to one of our image types
        if (($matches[2] * $matches[3]) < 40000) {
            $params = ImageType::THUMB->value;
        }

        return [
            'resize:' . $params,
        ];
    }
}
