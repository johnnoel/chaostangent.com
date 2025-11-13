<?php

declare(strict_types=1);

namespace App\Image\Action;

readonly final class Contrast implements Action
{
    public function __construct(public int $level)
    {
    }

    public static function createFromParameters(string $parameters): self
    {
        return new self(intval($parameters));
    }

    public function __toString(): string
    {
        return sprintf('contrast:%d', $this->level);
    }

    public function getAlias(): string
    {
        return 'contrast';
    }
}
