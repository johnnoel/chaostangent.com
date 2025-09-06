<?php

declare(strict_types=1);

namespace App\Image;

readonly final class Source
{
    /**
     * @param array<Action> $actions
     */
    public function __construct(public string $src, public array $actions, public ?string $caption = null)
    {
    }

    /**
     * @return array{w: int, h: int}
     */
    public function getTargetSize(): array
    {
        $w = 0;
        $h = 0;

        $resizeActions = array_filter($this->actions, fn (Action $a): bool => $a->action === 'resize');

        if (count($resizeActions) > 0) {
            $a = end($resizeActions);
            [ 'w' => $w, 'h' => $h ] = $a->getResizeParameters();
        }

        return [ 'w' => $w, 'h' => $h ];
    }
}
