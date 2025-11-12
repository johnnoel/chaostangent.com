<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Image\FileHandler;
use App\Image\ImageRepository;
use App\Image\LoadingType;
use App\Image\MimeType;
use App\Image\Source;
use App\Image\SourceFactory;
use App\Image\Variant;
use Symfony\Component\Asset\Packages;
use Symfony\Component\String\ByteString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @phpstan-type QuizImage array{src: string, actions: array<string>}
 * @phpstan-type QuizAnswer array{score: int, answer: string}
 * @phpstan-type QuizQuestion array{question: string, image: QuizImage, answers: array<QuizAnswer>}
 * @phpstan-type MapPoint array{lat: float, lng: float, title: string, caption?: string, colour?: int}
 * @phpstan-type MapRoute array{lat: float, lng: float}
 */
class MediaExtension extends AbstractExtension
{
    public function __construct(
        private ImageRepository $imageRepository,
        private readonly SourceFactory $sourceFactory,
        private readonly FileHandler $fileHandler,
        private readonly Packages $packages
    ) {
    }

    public function setImageRepository(ImageRepository $imageRepository): void
    {
        $this->imageRepository = $imageRepository;
    }

    /** @inheritdoc */
    public function getFunctions()
    {
        return [
            new TwigFunction('thumbnails', [ $this, 'thumbnails' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('slideshow', [ $this, 'slideshow' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('picture', [ $this, 'something' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('video', [ $this, 'video' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('passthrough', [ $this, 'passthrough' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('quiz', [ $this, 'quiz' ], [ 'is_safe' => [ 'html' ]]),
            new TwigFunction('map', [ $this, 'map' ], [ 'is_safe' => [ 'html' ]]),
        ];
    }

    /**
     * @param array<array{src: string, link?: string, caption?: string, actions: array<string>}> $sources
     */
    public function thumbnails(array $sources, bool $showCaptions = false, ?string $classes = null): string
    {
        $s = array_map(
            fn (array $s): Source => $this->sourceFactory->createSource(
                $s['src'],
                $s['actions'],
                $s['caption'] ?? null,
                $s['link'] ?? null,
            ),
            $sources
        );

        $map = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
        ];
        $count = $map[count($s)];
        $images = implode("\n", array_map(fn (Source $s): string => $this->picture($s, $showCaptions), $s));
        $captionsClass = ($showCaptions) ? ' -captions' : '';
        $extraClasses = ($classes !== null) ? ' ' . ltrim($classes) : '';

        return <<<HTML
            <div class="image-grid -{$count}{$captionsClass}{$extraClasses}">
                $images
            </div>
        HTML;
    }

    /**
     * @param array<array{src: string, actions: array<string>, caption?: string, link?: string}> $sources
     */
    public function slideshow(array $sources): string
    {
        $s = array_map(
            fn (array $s): Source => $this->sourceFactory->createSource(
                $s['src'],
                $s['actions'],
                $s['caption'] ?? null,
                $s['link'] ?? null,
            ),
            $sources
        );
        $slides = implode('', array_map([ $this, 'slide' ], $s));
        $nav = implode('', array_map(fn (int $i) => <<<HTML
            <button class="bullet" data-idx="$i"></button>
        HTML, array_keys($s)));
        $svg = $this->packages->getUrl('icons.svg');

        return <<<HTML
            <div class="image-slideshow">
                <div class="track">
                    <div class="container">
                        $slides
                    </div>
                </div>

                <div class="controls">
                    <div class="bullets">$nav</div>

                    <button class="toggle -pause">
                        <svg class="play"><use xlink:href="$svg#icon-play"></use></svg>
                        <svg class="pause"><use xlink:href="$svg#icon-pause"></use></svg>
                    </button>
                    <button class="left"><svg><use xlink:href="$svg#icon-back"></use></svg></button>
                    <button class="right"><svg><use xlink:href="$svg#icon-forward"></use></svg></button>
                </div>
            </div>
        HTML;
        /*
                <div class="bullets glide__bullets" data-glide-el="controls[nav]">
                    $nav
                </div>
                <div class="controls glide__arrows" data-glide-el="controls">
                    <button class="toggle -pause">
                        <svg class="play"><use xlink:href="$svg#icon-play"></use></svg>
                        <svg class="pause"><use xlink:href="$svg#icon-pause"></use></svg>
                    </button>
                    <button class="left glide__arrow glide__arrow--left" data-glide-dir="<">
                        <svg><use xlink:href="$svg#icon-back"></use></svg>
                    </button>
                    <button class="right glide__arrow glide__arrow--right" data-glide-dir=">">
                        <svg><use xlink:href="$svg#icon-forward"></use></svg>
                    </button>
                </div>
         */
    }

    /**
     * @param array{src: string|null, actions: array<string>} $source
     */
    public function something(array $source): string
    {
        if ($source['src'] === null) {
            return '';
        }

        $s = $this->sourceFactory->createSource($source['src'], $source['actions']);
        $variants = $this->imageRepository->getVariants($s);
        $sources = implode("\n", array_map([ $this, 'source' ], $variants));

        return <<<HTML
            <picture>
                $sources
            </picture>
        HTML;
    }

    /**
     * @param array<array{src: string, type: ?string}> $sources
     */
    public function video(
        array $sources,
        ?string $poster = null,
        ?float $ratio = 16 / 9,
        ?string $subtitles = null
    ): string {
        if ($poster !== null) {
            $posterVariant = $this->imageRepository->getVariants(
                $this->sourceFactory->createSource($poster, [ 'resize:poster' ]),
                [ MimeType::JPEG ]
            );
            $poster = array_first($posterVariant)?->src;
        }

        $p = ($poster !== null) ? ' poster="' . $poster . '"' : '';
        $s = implode("\n", array_map(function (array $s): string {
            $type = ($s['type'] !== null) ? ' type="' . $s['type'] . '"' : '';
            $src = $this->fileHandler->getSourceUrl($this->sourceFactory->createSource($s['src'], []));

            return sprintf('<source src="%s"%s>', $src, $type);
        }, $sources));

        $t = '';
        if ($subtitles !== null) {
            $t = <<<HTML
                <track label="English" kind="subtitles" srclang="en" src="/media/$subtitles" default>
            HTML;
        }

        $width = 544;
        $height = intval(round($width / $ratio));

        return <<<HTML
            <video controls preload="auto" width="$width" height="$height"$p>
                $s
                $t
            </video>
        HTML;
    }

    /**
     * @param array<array{src: string, caption?: string}> $sources
     */
    public function passthrough(array $sources, ?string $classes = null): string
    {
        $images = implode("\n", array_map(function (array $s): string {
            $caption = $s['caption'] ?? '';
            $source = $this->fileHandler->getSourceUrl(new Source($s['src'], []));

            return <<<HTML
                <img src="$source" alt="$caption">
            HTML;
        }, $sources));

        $extraClasses = ($classes !== null) ? ' ' . ltrim($classes) : '';

        return <<<HTML
            <div class="image-row{$extraClasses}">
                $images
            </div>
        HTML;
    }

    /**
     * @param array<QuizQuestion> $questions
     */
    public function quiz(array $questions): string
    {
        foreach ($questions as &$question) {
            $source = $this->sourceFactory->createSource($question['image']['src'], $question['image']['actions']);
            $question['images'] = $this->imageRepository->getVariants($source);
            unset($question['image']);
        }

        $json = json_encode($questions);

        return <<<HTML
            <script id="js-quiz-data" type="application/json">
                $json
            </script>
            <div id="js-quiz" data-questions="#js-quiz-data">
                <noscript class="message">You can only take the quiz if you have JavaScript enabled</noscript>
            </div>
        HTML;
    }

    /**
     * @param array<MapPoint> $points
     * @param array<MapRoute> $routes
     * @param array{0: float, 1: float}|null $centre
     */
    public function map(array $points = [], array $routes = [], ?array $centre = null, ?int $zoom = null): string
    {
        $id = ByteString::fromRandom(16);
        $pointsJson = json_encode($points);
        $routesJson = json_encode($routes);
        $zoom ??= 10;
        $centre = json_encode($centre ?? [ 0, 0 ]);

        return <<<HTML
            <script id="js-map-points-$id" type="application/json">
                $pointsJson
            </script>
            <script id="js-map-routes-$id" type="application/json">
                $routesJson
            </script>
            <div
                class="map-embed js-map"
                data-points="#js-map-points-$id"
                data-routes="#js-map-routes-$id"
                data-centre="$centre"
                data-zoom="$zoom"
            ></div>
        HTML;
    }

    private function picture(Source $source, bool $showCaption = false): string
    {
        $variants = $this->imageRepository->getVariants($source);
        $sources = implode("\n", array_map([ $this, 'source' ], $variants));
        $link = $source->link ?? $this->fileHandler->getSourceUrl($source);
        $caption = ($showCaption && $source->caption !== null) ? $source->caption : '';

        return <<<HTML
            <a href="$link">
                <picture>
                    $sources
                </picture>
                $caption
            </a>
        HTML;
    }

    private function slide(Source $source): string
    {
        $variants = $this->imageRepository->getVariants($source);
        $sources = implode("\n", array_map([ $this, 'source' ], $variants));
        $caption = ($source->caption !== null) ? "<span>$source->caption</span>" : '';
        $link = ($source->link !== null) ? $source->link : $this->fileHandler->getSourceUrl($source);

        return <<<HTML
            <div class="slide">
                <a href="$link">
                    <picture>
                        $sources
                    </picture>
                    $caption
                </a>
            </div>
        HTML;
    }

    private function source(Variant $variant, LoadingType $loadingType = LoadingType::LAZY): string
    {
        $width = ($variant->width > 0) ? 'width="' . $variant->width . '"' : '';
        $height = ($variant->height > 0) ? 'height="' . $variant->height . '"' : '';

        if ($variant->mimeType === MimeType::JPEG) {
            $loading = $loadingType->value;

            return <<<HTML
                <img src="$variant->src" alt="" $width $height loading="$loading">
            HTML;
        }

        return <<<HTML
            <source
                srcset="$variant->src"
                type="{$variant->mimeType->value}"
                $width
                $height
            >
        HTML;
    }
}
