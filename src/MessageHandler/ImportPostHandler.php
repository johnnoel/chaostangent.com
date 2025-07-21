<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ImportPost;
use App\Repository\PostRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webuni\FrontMatter\Twig\TwigCommentFrontMatter;

#[AsMessageHandler]
readonly final class ImportPostHandler
{
    public function __construct(private PostRepository $postRepository)
    {
    }

    public function __invoke(ImportPost $message): void
    {
        $content = $message->content;
        $frontmatter = TwigCommentFrontMatter::create();

        if (!$frontmatter->exists($content)) {
            throw new Exception('No frontmatter found');
        }

        $document = $frontmatter->parse($content);
        /** @var array{date?: string, alias?: string} $data */
        $data = $document->getData();
        $content = $document->getContent();

        // for now just assume we're updating
        $alias = $data['alias'] ?? '';
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $data['date'] ?? '');

        if ($alias === '' || $date === false) {
            throw new Exception('Unable to parse frontmatter');
        }

        $post = $this->postRepository->getPost($alias, intval($date->format('Y')), intval($date->format('m')));

        if ($post === null) {
            throw new Exception(
                sprintf('Unable to find post: %d/%d/%s', $alias, $date->format('Y'), $date->format('m'))
            );
        }

        $post->setContent($content);
        $this->postRepository->update($post);
    }
}
