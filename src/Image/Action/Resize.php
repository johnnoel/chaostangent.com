<?php

declare(strict_types=1);

namespace App\Image\Action;

use InvalidArgumentException;

readonly final class Resize implements Action
{
    public function __construct(public ?int $width, public ?int $height)
    {
    }

    public static function createFromParameters(string $parameters): self
    {
        $wh = explode('x', $parameters, 2);

        if (
            (count($wh) === 1 && $wh[0] === $parameters) ||
            (count($wh) === 2 && $wh[0] === '' && $wh[1] === '')
        ) {
            throw new InvalidArgumentException('Unable to parse resize parameters: ' . $parameters);
        }

        if ($wh[0] === '') {
            return new self(null, intval($wh[1]));
        } elseif ($wh[1] === '') {
            return new self(intval($wh[0]), null);
        }

        return new self(intval($wh[0]), intval($wh[1]));
    }

    public function __toString(): string
    {
        return sprintf('resize:%dx%d', $this->width, $this->height);
    }

    public function getAlias(): string
    {
        return 'resize';
    }
}
