<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Image\Action;
use App\Image\FileHandler;
use App\Message\TransformImage;
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class TransformImageHandler
{
    public function __construct(private FileHandler $fileHandler, private ImageManagerInterface $imageManager)
    {
    }

    public function __invoke(TransformImage $message): void
    {
        $source = $message->source;
        $targetMimeType = $message->mimeType;

        $sourcePath = $this->fileHandler->getSourcePath($source);
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return;
        }

        // devtodo Test if performing the actions, writing to a lossless file (e.g. PNG, TIFF) then re-encoding that
        // file to the different mime types is faster than re-reading and performing the actions
        $image = $this->imageManager->read($sourcePath, FilePathImageDecoder::class);

        foreach ($source->actions as $action) {
            switch ($action->action) {
                case 'crop':
                    $this->crop($action, $image);
                    break;
                case 'resize':
                    $this->resize($action, $image);
                    break;
            }
        }

        $image->encodeByMediaType($targetMimeType->value)
            ->save($this->fileHandler->getVariantPath($source, $targetMimeType))
        ;
    }

    private function crop(Action $action, ImageInterface $image): void
    {
        [ 'w' => $w, 'h' => $h, 'x' => $x, 'y' => $y ] = $action->getCropParameters();

        if ($w === 0 || $h === 0) {
            return;
        }

        $image->crop($w, $h, $x, $y);
    }

    private function resize(Action $action, ImageInterface $image): void
    {
        $resizeParams = $action->getResizeParameters();

        if (count($resizeParams) === 2) {
            $image->resize($resizeParams['w'], $resizeParams['h']);

            return;
        }

        $image->scale($resizeParams['w'] ?? null, $resizeParams['h'] ?? null);
    }
}
