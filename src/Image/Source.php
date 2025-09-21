<?php

declare(strict_types=1);

namespace App\Image;

use Stringable;

readonly final class Source implements Stringable
{
    /**
     * @param array<Action> $actions
     */
    public function __construct(public string $src, public array $actions, public ?string $caption = null)
    {
    }

    public function __toString(): string
    {
        $actions = implode("', '", $this->actions);

        if ($this->caption === null) {
            return sprintf("{ 'src': '%s', 'actions': '%s' }", $this->src, $actions);
        }

        // devtodo str_replace in the caption
        return sprintf("{ 'src': '%s', 'actions': '%s', 'caption': '%s' }", $this->src, $actions, $this->caption);
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

    public function isSameAs(Source $source): bool
    {
        if (implode($this->actions) !== implode($source->actions)) {
            return false;
        }

        return $this->src === $source->src && $this->caption === $source->caption;
    }
}
