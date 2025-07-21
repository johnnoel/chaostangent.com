<?php

declare(strict_types=1);

namespace App\Image;

use App\Message\ProcessImage;
use Symfony\Component\Messenger\MessageBusInterface;

readonly final class ProcessingImageRepository implements ImageRepository
{
    public function __construct(
        private ImageRepository $decoratedImageRepository,
        private MessageBusInterface $messageBus
    ) {
    }

    /** @inheritdoc */
    #[\Override]
    public function getVariants(Source $source): array
    {
        // devtodo find some way of reporting what source has been found
        $this->messageBus->dispatch(new ProcessImage($source));

        return $this->decoratedImageRepository->getVariants($source);
    }
}
