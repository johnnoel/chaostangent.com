<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use App\Extension\Twig\ImageExtension;
use App\Image\GatheringImageRepository;
use App\Image\SourceFactory;
use App\Message\ProcessImage;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

/**
 * Generate all the image variants for a post
 */
readonly final class GenerateImagesProcessor implements Processor
{
    public function __construct(
        private ImageExtension $imageExtension,
        private GatheringImageRepository $imageRepository,
        private SourceFactory $sourceFactory,
        private MessageBusInterface $messageBus,
        private Environment $twig
    ) {
    }

    public function process(Post $post): void
    {
        $this->imageExtension->setImageRepository($this->imageRepository);

        $template = $this->twig->createTemplate($post->getContent(), $post->getAlias());
        $template->render();

        $sources = $this->imageRepository->sources;

        if ($post->getImage() !== null) {
            $sources[] = $this->sourceFactory->createSource(...$post->getImage());
        }

        if (count($sources) === 0) {
            return;
        }

        // devtodo Log how many sources have been found

        foreach ($sources as $source) {
            $this->messageBus->dispatch(new ProcessImage($source));
        }

        $this->imageRepository->reset();
    }

    public function getSlug(): string
    {
        return 'generate-images';
    }
}
