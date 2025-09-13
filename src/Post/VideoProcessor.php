<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use Symfony\Component\DomCrawler\Crawler;

readonly final class VideoProcessor implements Processor
{
    #[\Override]
    public function process(Post $post): void
    {
        $post->setContent($this->transformVideos($post->getContent()));
        $post->setSummary($this->transformVideos($post->getSummary()));
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'video';
    }

    /**
     * @return ($content is string ? string : null)
     */
    private function transformVideos(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $regex = '#(<p>\s*)?<video(.*?)</video>(\s*</p>)?#s';
        $matches = [];
        $matchCount = preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount === 0) {
            return $content;
        }

        for ($i = $matchCount - 1; $i >= 0; $i--) {
            $srcs = implode(",\n", array_map(
                fn (array $s): string => sprintf(
                    "{ 'src': '%s', 'type': %s }",
                    $s['src'],
                    $s['type'] ? "'{$s['type']}'" : 'null'
                ),
                $this->getSources($matches[0][$i][0])
            ));

            $poster = $this->getPoster($matches[0][$i][0]);
            $replacement = ($poster === null) ?
                sprintf('{{ video([ %s ]) }}', $srcs) :
                sprintf("{{ video([ %s ], '%s') }}", $srcs, $poster)
            ;

            $length = strlen($matches[0][$i][0]);
            $offset = $matches[0][$i][1];
            $content = substr_replace($content, $replacement, $offset, $length);
        }

        return $content;
    }

    /**
     * @return array<array{src: string, type: ?string}>
     */
    private function getSources(string $video): array
    {
        $crawler = new Crawler($video);
        $sourceNodes = $crawler->filter('source');

        if ($sourceNodes->count() === 0) {
            return [];
        }

        $sources = [];
        $sourceNodes->each(function (Crawler $node) use (&$sources): void {
            $src = $node->attr('src');
            $type = $node->attr('type');

            if ($src === null) {
                return;
            }

            if (str_starts_with($src, 'https://chaostangent.com/media/')) {
                $src = substr($src, strlen('https://chaostangent.com/media/'));
            }

            $sources[] = [ 'src' => urldecode($src), 'type' => $type ];
        });

        return $sources;
    }

    private function getPoster(string $video): ?string
    {
        $posterMatches = [];
        if (preg_match('#poster="(.*?)"#s', $video, $posterMatches) !== 1) {
            return null;
        }

        $poster = $posterMatches[1];

        if (str_starts_with($poster, 'https://chaostangent.com/media/')) {
            $poster = substr($poster, strlen('https://chaostangent.com/media/'));
        }

        $regex = '#^(.*?)-?\d{3}x\d{3}\.jpg$#s';
        $matches = [];

        if (preg_match($regex, $poster, $matches) === 1) {
            $poster = $matches[1] . '.jpg';
        }

        return $poster;
    }
}
