<?php

declare(strict_types=1);

namespace App\Repository;

readonly final class FeatureRepository
{
    /**
     * @param array<string,bool> $features
     */
    public function __construct(private array $features = [])
    {
    }

    public function isFeatureEnabled(string $key): bool
    {
        return array_key_exists($key, $this->features) && $this->features[$key];
    }
}
