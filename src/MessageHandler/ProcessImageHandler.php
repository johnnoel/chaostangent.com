<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Image\FileHandler;
use App\Image\MimeType;
use App\Image\Source;
use App\Message\ProcessImage;
use App\Message\TransformImage;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ProcessImageHandler
{
    private const array MIME_TYPES = [
        MimeType::AVIF, MimeType::WEBP, MimeType::JPEG,
    ];

    public function __construct(private FileHandler $fileHandler, private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(ProcessImage $message): void
    {
        $source = $message->source;

        if ($this->isRemote($source)) {
            // fetch the source if we don't already have it
            // check if we've already downloaded the file
            throw new Exception('Remote files not yet supported');
        }

        $mimeTypes = array_filter(
            self::MIME_TYPES,
            fn (MimeType $mt): bool => $this->fileHandler->isStale($source, $mt)
        );

        // generate the different variants based on actions
        foreach ($mimeTypes as $mimeType) {
            $this->messageBus->dispatch(new TransformImage($source, $mimeType));
        }
    }

    private function isRemote(Source $source): bool
    {
        return str_starts_with('https://', $source->src) || str_starts_with('http://', $source->src);
    }
}
