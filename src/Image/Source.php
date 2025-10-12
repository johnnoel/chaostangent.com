<?php

declare(strict_types=1);

namespace App\Image;

use Stringable;

readonly final class Source implements Stringable
{
    /**
     * @param array<Action> $actions
     */
    public function __construct(
        public string $src,
        public array $actions,
        public ?string $caption = null,
        public ?string $variant = null
    ) {
    }

    public function __toString(): string
    {
        $actions = implode("', '", $this->actions);

        if ($this->caption === null) {
            return sprintf("{ 'src': '%s', 'actions': [ '%s' ] }", $this->src, $actions);
        }

        // devtodo str_replace in the caption
        return sprintf("{ 'src': '%s', 'actions': [ '%s' ], 'caption': '%s' }", $this->src, $actions, $this->caption);
    }

    /**
     * @return array{w?: int, h?: int}
     */
    public function getTargetSize(): array
    {
        $w = null;
        $h = null;

        foreach ($this->actions as $action) {
            if ($action->action === 'crop') {
                [ 'w' => $w, 'h' => $h ] = $action->getCropParameters();
            } elseif ($action->action === 'resize') {
                $resizeParams = $action->getResizeParameters();

                if (array_key_exists('w', $resizeParams) && array_key_exists('h', $resizeParams)) {
                    $w = $resizeParams['w'];
                    $h = $resizeParams['h'];
                } elseif (array_key_exists('w', $resizeParams) && $h !== null) {
                    $h = intval(round(($h / $w) * $resizeParams['w']));
                    $w = $resizeParams['w'];
                } elseif (array_key_exists('w', $resizeParams)) {
                    $w = $resizeParams['w'];
                } elseif (array_key_exists('h', $resizeParams) && $w !== null) {
                    $w = intval(round(($w / $h) * $resizeParams['h']));
                    $h = $resizeParams['h'];
                } elseif (array_key_exists('h', $resizeParams)) {
                    $h = $resizeParams['h'];
                }
            }
        }

        return array_filter([ 'w' => $w, 'h' => $h ]);
    }

    public function isSameAs(Source $source): bool
    {
        if (implode($this->actions) !== implode($source->actions)) {
            return false;
        }

        return $this->src === $source->src && $this->caption === $source->caption;
    }
}
