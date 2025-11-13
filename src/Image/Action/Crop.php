<?php

declare(strict_types=1);

namespace App\Image\Action;

use InvalidArgumentException;

readonly final class Crop implements Action
{
    public function __construct(public int $width, public int $height, public int $x, public int $y)
    {
    }

    public static function createFromParameters(string $parameters): self
    {
        $regex = '/^(\d+)x(\d+)([+-]\d+)([+-]\d+)$/';
        $matches = [];

        if (preg_match($regex, $parameters, $matches) !== 1) {
            throw new InvalidArgumentException('Unable to parse crop parameters: ' . $parameters);
        }

        return new self(
            intval($matches[1]),
            intval($matches[2]),
            intval($matches[3]),
            intval($matches[4])
        );
    }

    public function __toString(): string
    {
        return sprintf('crop:%dx%d%+d%+d', $this->width, $this->height, $this->x, $this->y);
    }

    public function getAlias(): string
    {
        return 'crop';
    }
}
