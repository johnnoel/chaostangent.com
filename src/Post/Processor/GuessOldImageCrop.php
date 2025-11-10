<?php

declare(strict_types=1);

namespace App\Post\Processor;

use App\Entity\Post;
use App\Image\Action;
use App\Image\CropGuesser;
use App\Image\FileHandler;
use App\Image\Source;

readonly final class GuessOldImageCrop implements Processor
{
    public function __construct(private FileHandler $fileHandler)
    {
    }

    #[\Override]
    public function process(Post $post): void
    {
        $extra = $post->getExtra();

        if (
            !array_key_exists('image', $extra) ||
            !is_string($extra['image']) ||
            trim($extra['image']) === ''
        ) {
            return;
        }

        $croppedImage = $this->guessOldImageCrop(urldecode($extra['image']));

        if ($croppedImage === null) {
            return;
        }

        $post->setImage($croppedImage->toArray());
        unset($extra['image']);
        $post->setExtra($extra);
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'guess-old-image-crop';
    }

    private function guessOldImageCrop(string $oldImage): ?Source
    {
        $cropPath = $oldImage;
        if (str_starts_with($cropPath, '/media/')) {
            $cropPath = substr($cropPath, strlen('/media/'));
        }

        $fullCropPath = $this->fileHandler->getSourcePath(new Source($cropPath, []));
        if (!file_exists($fullCropPath)) {
            return null;
        }

        $cropSizes = getimagesize($fullCropPath);
        if ($cropSizes === false) {
            return null;
        }

        $count = 0;
        $srcPath = preg_replace('/-\d+x\d+\.jpg$/', '.jpg', $cropPath, count: $count);
        if ($srcPath === null || $count === 0) { // can't differentiate between the cropped image and its source
            return null;
        }

        $fullSrcPath = $this->fileHandler->getSourcePath(new Source($srcPath, []));

        if (!file_exists($fullSrcPath)) {
            // try a png source
            $fullSrcPath = substr($fullSrcPath, 0, -3) . 'png';

            if (!file_exists($fullSrcPath)) {
                return null;
            }
        }

        $srcSizes = getimagesize($fullSrcPath);
        if ($srcSizes === false) {
            return null;
        }

        $cropGuesser = new CropGuesser();
        try {
            $crop = $cropGuesser->guessCrop($fullSrcPath, $fullCropPath);

            $ret = new Source($srcPath, [
                new Action('crop', sprintf('%dx%d+%d+%d', $crop['w'], $crop['h'], $crop['x'], $crop['y'])),
                new Action('resize', sprintf('%dx%d', $cropSizes[0], $cropSizes[1])),
            ]);

            return $ret;
        } catch (\Exception) {
        }

        return null;
    }
}
