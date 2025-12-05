<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;

trait CalculatesETags
{
    private string $assetManifestPath;

    private function calculateETag(DateTimeImmutable $lastModified = new DateTimeImmutable('now')): string
    {
        return hash_file('crc32', $this->assetManifestPath) . hash('crc32', $lastModified->format('U'));
    }
}
