<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Repository\FeatureRepository;
use Phiki\Grammar\Grammar;
use Phiki\Phiki;
use Phiki\Theme\Theme;
use Phiki\Transformers\Decorations\PreDecoration;
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
            new TwigFilter('gravatar', [ $this, 'gravatar' ]),
            new TwigFilter('code', [ $this, 'codeBlock' ], [ 'is_safe' => [ 'html' ] ]),
        ];
    }

    /** @inheritdoc */
    public function getFunctions()
    {
        return [
            new TwigFunction('isFeatureEnabled', [ $this, 'isFeatureEnabled' ]),
        ];
    }

    public function gravatar(
        string $email,
        string $default = 'indenticon',
        int $size = 64,
        string $rating = 'r'
    ): string {
        $email = trim(mb_strtolower($email));

        return sprintf(
            '//www.gravatar.com/avatar/%s?%s',
            hash('md5', $email),
            http_build_query([
                'd' => $default,
                's' => $size,
                'r' => $rating,
            ])
        );
    }

    public function isFeatureEnabled(string $key): bool
    {
        return $this->featureRepository->isFeatureEnabled($key);
    }

    public function codeBlock(string $code, string $language, Theme $theme = Theme::GithubDarkDimmed): string
    {
        return (new Phiki())
            ->codeToHtml(trim($code), Grammar::from($language), $theme)
            ->decoration(PreDecoration::make()->class('code-block'))
            ->withGutter()
            ->toString()
        ;
    }
}
