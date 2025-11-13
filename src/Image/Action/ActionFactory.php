<?php

declare(strict_types=1);

namespace App\Image\Action;

use InvalidArgumentException;

readonly final class ActionFactory
{
    /**
     * @param array<string,array{w?: int, h?: int}> $resizeAliases
     */
    public function __construct(private array $resizeAliases)
    {
    }

    public function createAction(string $signature): Action
    {
        $parts = explode(':', $signature, 2);

        if (
            (count($parts) === 1 && $parts[0] === $signature) ||
            (count($parts) === 2 && $parts[0] === '' && $parts[1] === '')
        ) {
            throw new InvalidArgumentException('Unable to to create an action from signature: ' . $signature);
        }

        [ $alias, $parameters ] = $parts;

        // devtodo change this to some kind of preprocessor system
        if ($alias === 'resize' && array_key_exists($parameters, $this->resizeAliases)) {
            $p = $this->resizeAliases[$parameters];
            $parameters = sprintf('%sx%s', $p['w'] ?? null, $p['h'] ?? null);
        }

        return match ($alias) {
            'resize' => Resize::createFromParameters($parameters),
            'crop' => Crop::createFromParameters($parameters),
            'sharpen' => Sharpen::createFromParameters($parameters),
            'contrast' => Contrast::createFromParameters($parameters),
            default => throw new InvalidArgumentException('Unable to create an action from signature: ' . $signature),
        };
    }
}
