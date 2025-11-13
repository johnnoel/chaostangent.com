<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Extension\Twig\MediaExtension;
use App\Image\GatheringImageRepository;
use App\Image\SourceFactory;
use App\Message\GenerateImages;
use App\Message\ProcessImage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

#[AsMessageHandler]
readonly final class GenerateImagesHandler
{
    public function __construct(
        private GatheringImageRepository $imageRepository,
        private MediaExtension $mediaExtension,
        private SourceFactory $sourceFactory,
        private Environment $twig,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(GenerateImages $message): void
    {
        $post = $message->post;
        $this->mediaExtension->setImageRepository($this->imageRepository);

        $this->twig->createTemplate(
            $post->getSummary() . $post->getContent(),
            $post->getAlias()
        )->render();

        $sources = $this->imageRepository->sources;

        if (is_array($post->getImage()) && $post->getImage()['src'] !== null) {
            $sources[] = $this->sourceFactory->createSource(...$post->getImage());
        }

        if (count($sources) === 0) {
            return;
        }

        $sources = array_unique($sources);
        $this->imageRepository->reset();
        foreach ($sources as $source) {
            $this->messageBus->dispatch(new ProcessImage($source, $message->force));
        }
    }
}
