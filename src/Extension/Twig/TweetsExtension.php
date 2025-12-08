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
use Illuminate\Support\Arr;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TweetsExtension extends AbstractExtension
{
    public function __construct(
        private ImageRepository $imageRepository,
        private readonly SourceFactory $sourceFactory,
        private readonly FileHandler $fileHandler,
    ) {
    }

    public function setImageRepository(ImageRepository $imageRepository): void
    {
        $this->imageRepository = $imageRepository;
    }

    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('tweet_entities', [ $this, 'entities' ], [ 'is_safe' => [ 'html' ] ]),
        ];
    }

    /**
     * @param array<mixed> $json
     */
    public function entities(string $content, array $json): string
    {
        if (Arr::has($json, 'tweet.extended_entities.media')) {
            /** @phpstan-ignore-next-line argument.type */
            $content = $this->media($content, $json['tweet']['id_str'], $json['tweet']['extended_entities']['media']);
        }

        return $content;
    }

    /**
     * @param array<array<mixed>> $mediaEntities
     */
    private function media(string $content, string $tweetId, array $mediaEntities): string
    {
        $sources = array_map(function (array $medium) use ($tweetId): Source {
            $filename = basename(strval(parse_url($medium['media_url'], PHP_URL_PATH)));

            return $this->sourceFactory->createSource(
                src: sprintf('tweets/%s-%s', $tweetId, $filename),
                actions: [ 'resize:thumb', 'sharpen:3' ],
            );
        }, $mediaEntities);

        $pictures = implode("\n", array_map([ $this, 'picture' ], $sources));
        /**
         * @var string $start
         * @var string $finish
         * @phpstan-ignore-next-line offsetAccess.nonArray
         */
        [ $start, $finish ] = $mediaEntities[0]['indices'];

        return substr_replace($content, $pictures, intval($start), (intval($finish) - intval($start)));
    }

    private function picture(Source $source, bool $showCaption = false): string
    {
        $variants = $this->imageRepository->getVariants($source);
        $sources = implode("\n", array_map([ $this, 'variant' ], $variants));
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

    private function variant(Variant $variant, LoadingType $loadingType = LoadingType::LAZY): string
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
