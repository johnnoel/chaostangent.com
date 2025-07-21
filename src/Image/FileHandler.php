<?php

declare(strict_types=1);

namespace App\Image;

use DateTimeImmutable;

readonly final class FileHandler
{
    private string $mediaDirectory;
    private string $mediaUrl;

    public function __construct(string $mediaDirectory, string $mediaUrl)
    {
        $this->mediaDirectory = rtrim($mediaDirectory, '/');
        $this->mediaUrl = rtrim($mediaUrl, '/');
    }

    public function getSourcePath(Source $source): string
    {
        return sprintf('%s/%s', $this->mediaDirectory, $source->src);
    }

    public function getSourceUrl(Source $source): string
    {
        return sprintf('%s/%s', $this->mediaUrl, $source->src);
    }

    public function getVariantPath(Source $source, MimeType $mimeType): string
    {
        $directory = dirname($source->src);

        return sprintf(
            '%s/%s%s',
            $this->mediaDirectory,
            (in_array($directory, [ '', '.' ])) ? '' : $directory . '/',
            $this->getVariantFilename($source, $mimeType)
        );
    }

    public function getVariantFilename(Source $source, MimeType $mimeType): string
    {
        $filename = basename($source->src);
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);

        [ 'w' => $w, 'h' => $h ] = $source->getTargetSize();

        return sprintf('%s-%dx%d.%s', $baseFilename, $w, $h, $mimeType->getExtension());
    }

    public function getVariantUrl(Source $source, MimeType $mimeType): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->mediaUrl,
            dirname($source->src),
            $this->getVariantFilename($source, $mimeType)
        );
    }

    public function isStale(Source $source, MimeType $mimeType): bool
    {
        $s = $this->getSourcePath($source);
        $v = $this->getVariantPath($source, $mimeType);

        if (!file_exists($s) || !file_exists($v)) {
            return true;
        }

        $sourceLastModified = filemtime($s);
        $variantLastModified = filemtime($v);

        if ($sourceLastModified === false || $variantLastModified === false) {
            return true;
        }

        $sourceLastModified = DateTimeImmutable::createFromTimestamp($sourceLastModified);
        $variantLastModified = DateTimeImmutable::createFromTimestamp($variantLastModified);

        return $variantLastModified < $sourceLastModified;
    }
}
