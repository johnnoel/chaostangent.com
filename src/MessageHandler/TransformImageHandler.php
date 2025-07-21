<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Image\FileHandler;
use App\Message\TransformImage;
use Intervention\Image\Decoders\FilePathImageDecoder;
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
                    [ 'w' => $w, 'h' => $h, 'x' => $x, 'y' => $y ] = $action->getCropParameters();
                    $image->crop($w, $h, $x, $y);
                    break;
                case 'resize':
                    [ 'w' => $w, 'h' => $h ] = $action->getResizeParameters();
                    $image->resize($w, $h);
                    break;
            }
        }

        $image->encodeByMediaType($targetMimeType->value)
            ->save($this->fileHandler->getVariantPath($source, $targetMimeType))
        ;
    }
}
