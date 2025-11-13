<?php

declare(strict_types=1);

namespace App\Image\Action;

readonly final class Sharpen implements Action
{
    public function __construct(public int $amount)
    {
    }

    public static function createFromParameters(string $parameters): self
    {
        return new self(intval($parameters));
    }

    public function __toString(): string
    {
        return sprintf('sharpen:%d', $this->amount);
    }

    public function getAlias(): string
    {
        return 'sharpen';
    }
}
