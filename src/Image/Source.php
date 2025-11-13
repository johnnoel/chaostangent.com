<?php

declare(strict_types=1);

namespace App\Image;

use App\Image\Action\Action;
use App\Image\Action\Crop;
use App\Image\Action\Resize;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;

/**
 * @implements Arrayable<string,string|array<mixed>>
 */
readonly final class Source implements Stringable, Arrayable
{
    /**
     * @param array<Action> $actions
     */
    public function __construct(
        public string $src,
        public array $actions,
        public ?string $caption = null,
        public ?string $variant = null,
        public ?string $link = null
    ) {
    }

    #[\Override]
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
     * @return array{src: string, actions: array<string>, caption?: string, variant?: string, link?: string}
     */
    #[\Override]
    public function toArray(): array
    {
        return array_merge([
            'src' => $this->src,
            'actions' => array_map(fn (Action $a): string => (string)$a, $this->actions),
        ], array_filter([
            'caption' => $this->caption,
            'variant' => $this->variant,
            'link' => $this->link,
        ]));
    }

    /**
     * @return array{w?: int, h?: int}
     */
    public function getTargetSize(): array
    {
        $w = null;
        $h = null;

        foreach ($this->actions as $action) {
            if ($action instanceof Crop) {
                $w = $action->width;
                $h = $action->height;
            } elseif ($action instanceof Resize) {
                if ($action->width !== null && $action->height !== null) {
                    $w = $action->width;
                    $h = $action->height;
                } elseif ($action->width !== null && $h !== null) {
                    $h = intval(round(($h / $w) * $action->width));
                    $w = $action->width;
                } elseif ($action->width !== null) {
                    $w = $action->width;
                } elseif ($action->height !== null && $w !== null) {
                    $w = intval(round(($w / $h) * $action->height));
                    $h = $action->height;
                } elseif ($action->height !== null) {
                    $h = $action->height;
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
