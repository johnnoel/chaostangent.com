<?php

declare(strict_types=1);

namespace App\Image;

use App\Image\Action\ActionFactory;

readonly final class SourceFactory
{
    public function __construct(private ActionFactory $actionFactory)
    {
    }

    /**
     * @param array<string> $actions
     */
    public function createSource(
        string $src,
        array $actions,
        ?string $caption = null,
        ?string $link = null
    ): Source {
        $actions = array_map([ $this->actionFactory, 'createAction' ], $actions);

        return new Source($src, $actions, $caption, link: $link);
    }
}
