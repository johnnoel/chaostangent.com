<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Image\Action\Contrast;
use App\Image\Action\Crop;
use App\Image\Action\Resize;
use App\Image\Action\Sharpen;
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
            switch ($action::class) {
                case Crop::class:
                    $this->crop($action, $image);
                    break;
                case Resize::class:
                    $this->resize($action, $image);
                    break;
                case Sharpen::class:
                    $image->sharpen($action->amount);
                    break;
                case Contrast::class:
                    $image->contrast($action->level);
                    break;
            }
        }

        $image->encodeByMediaType($targetMimeType->value)
            ->save($this->fileHandler->getVariantPath($source, $targetMimeType))
        ;
    }

    private function crop(Crop $crop, ImageInterface $image): void
    {
        if ($crop->width === 0 || $crop->height === 0) {
            return;
        }

        $image->crop($crop->width, $crop->height, $crop->x, $crop->y);
    }

    private function resize(Resize $resize, ImageInterface $image): void
    {
        if ($resize->width !== null && $resize->height !== null) {
            $image->resize($resize->width, $resize->height);

            return;
        }

        $image->scale($resize->width, $resize->height);
    }
}
