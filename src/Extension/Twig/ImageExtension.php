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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
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
        ];
    }

    /**
     * @param array<array{src: string, link?: string, caption?: string, actions: array<string>}> $sources
     */
    public function thumbnails(array $sources, bool $showCaptions = false): string
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

        return <<<HTML
            <div class="image-grid -{$count}{$captionsClass}">
                $images
            </div>
        HTML;
    }

    /**
     * @param array<array{src: string, actions: array<string>, caption?: string}> $sources
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
        $slides = implode("\n", array_map([ $this, 'slide' ], $s));
        $nav = implode("\n", array_map(fn (int $i) => <<<HTML
            <button class="glide__bullet" data-glide-dir="=$i"></button>
        HTML, array_keys($s)));
        $svg = $this->packages->getUrl('icons.svg');

        return <<<HTML
            <div class="image-slideshow glide">
                <div class="track glide__track" data-glide-el="track">
                    <div class="glide__slides">
                        $slides
                    </div>
                </div>
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
            </div>
        HTML;
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
        ?float $ratio = 16/9,
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

    public function passthrough(array $sources): string
    {
        $images = implode("\n", array_map(function (array $s): string {
            $caption = $s['caption'] ?? '';
            $source = $this->fileHandler->getSourceUrl(new Source($s['src'], []));

            return <<<HTML
                <img src="$source" alt="$caption">
            HTML;
        }, $sources));

        return <<<HTML
            <div class="image-row">
                $images
            </div>
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
            <div class="glide__slide">
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
