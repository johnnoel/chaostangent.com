<?php

declare(strict_types=1);

namespace App\Image;

use InvalidArgumentException;

readonly final class ActionFactory
{
    /**
     * @param array<string,array{w: int, h: int}> $resizeAliases
     */
    public function __construct(private array $resizeAliases)
    {
    }

    public function createAction(string $actionString): Action
    {
        if (!str_contains($actionString, ':')) {
            throw new InvalidArgumentException('Invalid action format');
        }

        [ $action, $parameters ] = explode(':', $actionString, 2);

        // rewrite parameters into the w x h format
        if ($action === 'resize' && array_key_exists($parameters, $this->resizeAliases)) {
            [ 'w' => $w, 'h' => $h ] = $this->resizeAliases[$parameters];
            $parameters = sprintf('%dx%d', $w, $h);
        }

        // devtodo return specific action classes, i.e. new CropAction, new ResizeAction
        return new Action($action, $parameters);
    }
}
