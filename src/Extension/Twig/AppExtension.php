<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    /** @inheritdoc */
    public function getFilters()
    {
        return [
            new TwigFilter('pullquote', [ $this, 'pullquote' ]),
            new TwigFilter('teaser', [ $this, 'teaser' ]),
        ];
    }

    public function pullquote(string $input): string
    {
        return $input;
    }

    public function teaser(string $input): string
    {
        return $input;
    }
}
