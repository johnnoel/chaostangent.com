<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

readonly final class SlideshowProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $post->setContent($this->transformSlideshows($post->getContent()));
        $post->setSummary($this->transformSlideshows($post->getSummary()));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'slideshow';
    }

    /**
     * @return ($content is string ? string : null)
     */
    private function transformSlideshows(?string $content): ?string
    {
        if ($content === null) {
            return $content;
        }

        $regex = '#<p class="slideshow">(.*?)</p>#s';
        $matches = [];
        $matchCount = preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount === 0) {
            return $content;
        }

        for ($i = $matchCount - 1; $i >= 0; $i--) {
            $slideshow = new Crawler($matches[0][$i][0]);
            $replacement = sprintf('{{ slideshow([ %s ]) }}', implode(",\n", $this->getSources($slideshow)));

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
