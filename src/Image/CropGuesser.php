<?php

declare(strict_types=1);

namespace App\Image;

use Exception;
use GdImage;
use LogicException;
use Symfony\Component\Process\Process;

readonly final class CropGuesser
{
    /**
     * @return array{w: int, h: int, x: int, y: int}
     */
    public function guessCrop(string $source, string $cropped): array
    {
        $srcContents = file_get_contents($source);

        if ($srcContents === false) {
            throw new Exception($source . ' not readable');
        }

        $src = imagecreatefromstring($srcContents);
        if (!($src instanceof GdImage)) {
            throw new Exception('Unable to read ' . $source . ' with GD');
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);
        $srcRatio = ($srcWidth / $srcHeight);

        $imageSize = getimagesize($cropped);
        if ($imageSize === false) {
            throw new Exception('Unable to read ' . $cropped);
        }

        [ 0 => $targetWidth, 1 => $targetHeight ] = $imageSize;
        $targetRatio = ($targetWidth / $targetHeight);
        $crop = imagecreatetruecolor(max($targetWidth, 1), max($targetHeight, 1)); // canvas for testing resized crops

        if ($crop === false) {
            throw new Exception('Unable to create crop target in GD');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'crop_guesser');

        if ($tempFile === false) {
            throw new Exception('Unable to create temporary file in ' . sys_get_temp_dir());
        }

        $comparisonValues = [];

        // if the source is landscape and the crop is landscape, iterate on the y axis assuming full width
        // if the source is portrait and the crop is landscape, iterate on the y axis assuming full width
        // if the source is square and the crop is landscape, iterate on the y axis assuming full width

        // if the source is portrait and the crop is portrait, iterate on the x axis assuming full height
        // if the source is landscape and the crop is portrait, iterator on the x axis assuming full height
        // if the source is square and the crop is portrait, iterate on the x axis assuming full height

        // if the source is landscape and the crop is square, iterate on the x axis assuming full height
        // if the source is portrait and the crop is square, iterate on the y axis assuming full width
        // if the source is square and the crop is square, crop = source

        $xMax = $srcWidth;
        $yMax = $srcHeight;
        $cropWidth = $srcWidth;
        $cropHeight = $srcHeight;

        if ($targetRatio > 1) { // landscape
            // fixed x, iterate y
            $cropHeight = min(intval(round($srcWidth / $targetRatio)), $srcHeight);
            $xMax = 0;
            $yMax = $srcHeight - $cropHeight;
        } elseif ($targetRatio < 1) { // portrait
            // fixed y, iterate x
            $cropWidth = min(intval(round($srcHeight / $targetHeight)), $srcWidth);
            $xMax = $srcWidth - $cropWidth;
            $yMax = 0;
        } else { // square
            if ($srcRatio > 1) {
                // fixed y, iterate x
                $cropWidth = $srcHeight;
                $xMax = $srcWidth - $srcHeight;
                $yMax = 0;
            } elseif ($srcRatio < 1) {
                // fixed x, iterate y
                $cropHeight = $srcWidth;
                $xMax = 0;
                $yMax = $srcHeight - $srcWidth;
            } else {
                // don't iterate anything, just resize as crop = source
                $xMax = 0;
                $yMax = 0;
            }
        }

        for ($x = 0; $x <= $xMax; $x++) {
            for ($y = 0; $y <= $yMax; $y++) {
                $currentCrop = imagecrop($src, [
                    'x' => $x,
                    'y' => $y,
                    'width' => $cropWidth,
                    'height' => $cropHeight,
                ]);

                if ($currentCrop === false) {
                    throw new Exception('Unable to create crop in GD');
                }

                // resize
                imagecopyresampled(
                    $crop,
                    $currentCrop,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $cropWidth,
                    $cropHeight
                );

                // devtodo sharpen

                // save to temp file
                imagejpeg($crop, $tempFile);

                // compare using gm compare
                $process = new Process([ 'gm', 'compare', '-metric', 'rmse', $cropped, $tempFile ]);
                $process->run();

                $key = sprintf('%d|%d|%d|%d', $cropWidth, $cropHeight, $x, $y);

                //echo $key . PHP_EOL;
                $comparisonValues[$key] = $this->getTotalFromOutput($process->getOutput());
            }
        }

        unlink($tempFile);
        asort($comparisonValues, SORT_NUMERIC);

        if (count($comparisonValues) === 0) {
            throw new LogicException('No crops detected');
        }

        $mostSimilar = array_key_first($comparisonValues);
        [ $w, $h, $x, $y ] = array_map('intval', explode('|', $mostSimilar));

        return [ 'w' => $w, 'h' => $h, 'x' => $x, 'y' => $y ];
    }

    private function getTotalFromOutput(string $output): float
    {
        $lines = explode(PHP_EOL, trim($output));
        $last = end($lines);

        $matches = [];
        $matched = preg_match('/Total: (\d+\.\d+)/', $last, $matches);

        if ($matched !== 1) {
            return 0.0;
        }

        return floatval($matches[1]);
    }
}
