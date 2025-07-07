<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Repository\FeatureRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly FeatureRepository $featureRepository)
    {
    }

    /** @inheritdoc */
    public function getFilters()
    {
        return [
            new TwigFilter('pullquote', [ $this, 'pullquote' ]),
            new TwigFilter('teaser', [ $this, 'teaser' ]),
            new TwigFilter('full', [ $this, 'full' ]),
        ];
    }

    /** @inheritdoc */
    public function getFunctions()
    {
        return [
            new TwigFunction('isFeatureEnabled', [ $this, 'isFeatureEnabled' ]),
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

    public function full(string $input): string
    {
        return $input;
    }

    public function isFeatureEnabled(string $key): bool
    {
        return $this->featureRepository->isFeatureEnabled($key);
    }
}
