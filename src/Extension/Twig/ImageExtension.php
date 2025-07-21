<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Image\ActionFactory;
use App\Image\FileHandler;
use App\Image\ImageRepository;
use App\Image\MimeType;
use App\Image\Source;
use App\Image\Variant;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageExtension extends AbstractExtension
{
    public function __construct(
        private ImageRepository $imageRepository,
        private readonly ActionFactory $actionFactory,
        private readonly FileHandler $fileHandler
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
        ];
    }

    /**
     * @param array<array{src: string, actions: array<string>}> $sources
     */
    public function thumbnails(array $sources): string
    {
        $s = [];
        foreach ($sources as $source) {
            $actions = array_map([ $this->actionFactory, 'createAction' ], $source['actions']);

            $s[] = new Source($source['src'], $actions);
        }

        $map = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
        ];
        $count = $map[count($s)];
        $images = implode("\n", array_map([ $this, 'picture' ], $s));

        return <<<HTML
            <div class="thumbnails $count">
                $images
            </div>
        HTML;
    }

    /**
     * @param array<array{src: string, actions: array<string>}> $sources
     */
    public function slideshow(array $sources): string
    {
        $images = implode("\n", array_map([ $this, 'picture' ], $sources));

        return <<<HTML
            <div class="slideshow">
                $images
            </div>
        HTML;
    }

    private function picture(Source $source): string
    {
        $variants = $this->imageRepository->getVariants($source);
        $sources = implode("\n", array_map([ $this, 'image' ], $variants));

        return <<<HTML
            <a href="{$this->fileHandler->getSourceUrl($source)}">
                <picture>
                    $sources
                </picture>
            </a>
        HTML;
    }

    private function image(Variant $variant): string
    {
        if ($variant->mimeType === MimeType::JPEG) {
            return <<<HTML
                <img src="$variant->src" alt="" width="$variant->width" height="$variant->height">
            HTML;
        }

        return <<<HTML
            <source
                srcset="$variant->src"
                type="{$variant->mimeType->value}"
                width="$variant->width"
                height="$variant->height"
            >
        HTML;
    }
}
