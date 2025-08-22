<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use DateTimeImmutable;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Export posts to YAML front-matter'd Twig files
 */
readonly final class ExportProcessor implements Processor
{
    public function __construct(
        private string $outputDirectory,
        private SerializerInterface $serializer,
    ) {
    }

    public function process(Post $post): void
    {
        $frontmatter = trim($this->serializer->serialize($post, 'yaml', context: [
            'groups' => [ 'frontmatter' ],
            'yaml_inline' => 2,
        ]));

        $export = <<<POST
            {#---
            $frontmatter
            ---#}
            {$post->getContent()}
            POST;

        $date = $post->getDate() ?: (new DateTimeImmutable('now'));
        $filename = sprintf('%s-%s.html.twig', $date->format('Y-m-d'), $post->getAlias());

        file_put_contents($this->outputDirectory . '/' . $filename, $export);
    }

    public function getSlug(): string
    {
        return 'export';
    }
}
