<?php

declare(strict_types=1);

namespace App\Image;

final class Block
{
    // phpcs:disable
    public int $length {
        get => strlen($this->content);
    }
    // phpcs:enable

    /**
     * @param array<Source> $sources
     */
    public function __construct(
        public readonly array $sources,
        public readonly string $content,
        public readonly int $offset
    ) {
    }
}
