<?php

declare(strict_types=1);

namespace App\Image\Action;

use Stringable;

interface Action extends Stringable
{
    public static function createFromParameters(string $parameters): self;

    public function getAlias(): string;
}
