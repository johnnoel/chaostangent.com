<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Repository\FeatureRepository;
use DateTimeImmutable;
use Phiki\Grammar\Grammar;
use Phiki\Phiki;
use Phiki\Theme\Theme;
use Phiki\Transformers\Decorations\PreDecoration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twitter\Text\Autolink;

final class AppExtension extends AbstractExtension
{
    private ?Autolink $autolink = null;

    public function __construct(private readonly FeatureRepository $featureRepository)
    {
    }

    /** @inheritdoc */
    public function getFilters()
    {
        return [
            new TwigFilter('gravatar', [ $this, 'gravatar' ]),
            new TwigFilter('code', [ $this, 'codeBlock' ], [ 'is_safe' => [ 'html' ] ]),
            new TwigFilter('tweet', [ $this, 'tweet' ], [ 'is_safe' => [ 'html' ] ]),
            new TwigFilter('age', [ $this, 'age' ]),
            new TwigFilter('search_results_summary', [ $this, 'searchResultsSummary' ], [ 'is_safe' => [ 'html' ] ]),
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
        string $default = 'identicon',
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

    public function tweet(string $tweet): string
    {
        if ($this->autolink === null) {
            $this->autolink = Autolink::create();
        }

        return $this->autolink->autoLink($tweet);
    }

    /**
     * @param string $birthday In Y-m-d format
     */
    public function age(string $birthday, DateTimeImmutable $now = new DateTimeImmutable('now')): int
    {
        $birthday = DateTimeImmutable::createFromFormat('Y-m-d', $birthday);

        if ($birthday === false) {
            return 0;
        }

        return $now->diff($birthday)->y;
    }

    public function searchResultsSummary(string $plainText, string $query): string
    {
        $position = strpos(mb_strtolower($plainText), mb_strtolower($query));
        if ($position === false) {
            return substr($plainText, 0, 255);
        }

        return trim(sprintf(
            '%s<mark>%s</mark>%s',
            substr($plainText, max(0, $position - 128), min($position, 128)),
            substr($plainText, $position, strlen($query)),
            substr($plainText, $position + strlen($query), 256 - min($position, 128))
        ));
    }
}
