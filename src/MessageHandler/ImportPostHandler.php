<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Post;
use App\Form\Model\PostModel;
use App\Form\Type\PostType;
use App\Message\ImportPost;
use App\Repository\PostRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webuni\FrontMatter\Twig\TwigCommentFrontMatter;

#[AsMessageHandler]
readonly final class ImportPostHandler
{
    public function __construct(
        private PostRepository $postRepository,
        private FormFactoryInterface $formFactory
    ) {
    }

    public function __invoke(ImportPost $message): void
    {
        $content = $message->content;
        $frontmatter = TwigCommentFrontMatter::create();

        if (!$frontmatter->exists($content)) {
            throw new Exception('No frontmatter found');
        }

        $document = $frontmatter->parse($content);
        /** @var array{alias?: string, date?: string} $data */
        $data = $document->getData();
        $content = $document->getContent();

        $postModel = new PostModel();
        $form = $this->formFactory->create(PostType::class, $postModel, [ 'csrf_protection' => false ]);
        $form->submit(array_merge($data, [ 'content' => $content ]));

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new Exception('Could not import post: ' . $form->getErrors(deep: true, flatten: true));
        }

        $alias = $data['alias'] ?? '';
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $data['date'] ?? '');

        if ($alias === '' || $date === false) {
            throw new Exception('Unable to parse frontmatter');
        }

        $post = $this->postRepository->getPost($alias, intval($date->format('Y')), intval($date->format('m')));

        if ($post === null) {
            $post = Post::create($postModel);
        }

        $post->update($postModel);
        $this->postRepository->update($post);
    }
}
