<?php

declare(strict_types=1);

namespace App\Post;

use Exception;

/**
 * Convert a block of images into a Twig function call suitable for {{ thumbnails }} or {{ slideshow }}
 */
readonly abstract class ImageBlockProcessor
{
    private const MAPPING = [
        'oldthumb' => '268x117',
        'oldlead' => '540x231',
        'oldposter' => '544x306',
        'oldsquare' => '320x320',
    ];

    abstract protected function getBlockRegex(): string;

    abstract protected function getTwigFunctionName(): string;

    protected function processImageBlocks(?string $content): ?string
    {
        if ($content === null) {
            return $content;
        }

        $thumbnailMatches = $this->getThumbnailBlocks($content);

        if (count($thumbnailMatches) === 0) {
            return $content;
        }

        $thumbnailsMatched = count($thumbnailMatches[0]);

        // go in reverse order so we don't have to mess with offsets when replacing
        for ($i = $thumbnailsMatched - 1; $i >= 0; $i--) {
            $sourceMatches = $this->getSourceBlocks($thumbnailMatches[1][$i][0]);

            if (count($sourceMatches) === 0) {
                continue;
            }

            $sources = [];

            foreach ($sourceMatches as $source) {
                if (!str_starts_with($source[1], 'https://chaostangent.com/media/')) {
                    // skip the entire block
                    continue 2;
                }

                $path = substr(strval(parse_url($source[1], PHP_URL_PATH)), 7);
                $qs = [];
                $queryString = parse_url(html_entity_decode($source[2]), PHP_URL_QUERY);

                if (!is_string($queryString)) {
                    // skip the entire block
                    continue 2;
                }

                parse_str($queryString, $qs);

                if (!array_key_exists('g', $qs) || !is_string($qs['g']) || !array_key_exists($qs['g'], self::MAPPING)) {
                    throw new Exception('Unknown group found: ' . var_export($qs['g'], true));
                }

                $actions = [
                    'crop:' . ((is_string($qs['c'])) ? $qs['c'] : '0x0+0+0'),
                    'resize:' . self::MAPPING[$qs['g']],
                ];

                $sources[] = sprintf("{ 'src': '%s', 'actions': [ '%s' ] }", $path, implode("', '", $actions));
            }

            $replacement = sprintf('{{ %s([ %s ]) }}', $this->getTwigFunctionName(), implode(",\n", $sources));

            $length = strlen($thumbnailMatches[0][$i][0]);
            $offset = $thumbnailMatches[0][$i][1];
            $content = substr_replace($content, $replacement, $offset, $length);
        }

        return $content;
    }

    /**
     * @return array<array<array{0: string, 1: int}>>
     */
    private function getThumbnailBlocks(string $content): array
    {
        $regex = $this->getBlockRegex();
        $thumbnailMatches = [];
        $thumbnailsMatched = preg_match_all(
            $regex,
            $content,
            $thumbnailMatches,
            PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($thumbnailsMatched === 0 || $thumbnailsMatched === false) {
            return [];
        }

        return $thumbnailMatches;
    }

    /**
     * @return array<array<string>>
     */
    private function getSourceBlocks(string $content): array
    {
        $regex = '#<a href="(.*?)"><img src="(.*?)".*?</a>#s';
        $sourceMatches = [];
        $sourcesMatched = preg_match_all($regex, $content, $sourceMatches, PREG_SET_ORDER);

        if ($sourcesMatched === 0 || $sourcesMatched === false) {
            return [];
        }

        return $sourceMatches;
    }
}
