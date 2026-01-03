<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Image\FileHandler;
use App\Image\ImageRepository;
use App\Image\MimeType;
use App\Image\Source;
use App\Image\SourceFactory;
use App\Image\Variant;
use Symfony\Component\String\ByteString;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
    /** @var array<array{0?: int, 1?: int, mime?: string}> $imageSizes */
    private array $imageSizes = [];

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

    /** @inheritdoc */
    public function getFunctions()
    {
        $options = [
            'is_safe' => [ 'html' ],
            'needs_environment' => true,
        ];

        return [
            new TwigFunction('thumbnails', [ $this, 'thumbnails' ], $options),
            new TwigFunction('slideshow', [ $this, 'slideshow' ], $options),
            new TwigFunction('picture', [ $this, 'picture' ], $options),
            new TwigFunction('passthrough', [ $this, 'passthrough' ], $options),
            new TwigFunction('video', [ $this, 'video' ], $options),
            new TwigFunction('quiz', [ $this, 'quiz' ], $options),
            new TwigFunction('map', [ $this, 'map' ], $options),
        ];
    }

    /** @inheritdoc */
    public function getFilters()
    {
        return [
            new TwigFilter('image_type', [ $this, 'imageType' ]),
            new TwigFilter('image_width', [ $this, 'imageWidth' ]),
            new TwigFilter('image_height', [ $this, 'imageHeight' ]),
        ];
    }

    /**
     * @param array<array{src: string, link?: string, caption?: string, actions: array<string>}> $sources
     */
    public function thumbnails(
        Environment $twig,
        array $sources,
        bool $showCaptions = false,
        ?string $classes = null,
        ?string $description = null,
    ): string {
        $s = array_map(
            fn (array $s): Source => $this->sourceFactory->createSource(
                $s['src'],
                $s['actions'],
                $s['caption'] ?? null,
                $s['link'] ?? null,
            ),
            $sources
        );

        $images = array_map(function (Source $source): object {
            $variants = $this->imageRepository->getVariants($source);
            $link = $source->link ?? $this->fileHandler->getSourceUrl($source);

            return new readonly class ($link, $variants, $source->caption) {
                /**
                 * @param array<Variant> $variants
                 */
                public function __construct(public string $link, public array $variants, public ?string $caption)
                {
                }
            };
        }, $s);

        return $twig->render('media/image-grid.html.twig', [
            'images' => $images,
            'show_captions' => $showCaptions,
            'classes' => $classes,
            'description' => $description,
        ]);
    }

    /**
     * @param array<array{src: string, actions: array<string>, caption?: string, link?: string}> $sources
     */
    public function slideshow(Environment $twig, array $sources): string
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

        $slides = array_map(function (Source $s): object {
            $variants = $this->imageRepository->getVariants($s);
            $link = $s->link ?? $this->fileHandler->getSourceUrl($s);

            return new readonly class ($variants, $link, $s->caption) {
                /**
                 * @param array<Variant> $variants
                 */
                public function __construct(
                    public array $variants,
                    public ?string $link = null,
                    public ?string $caption = null
                ) {
                }
            };
        }, $s);

        return $twig->render('media/slideshow.html.twig', [ 'slides' => $slides ]);
    }

    /**
     * @param array{src: string|null, actions: array<string>} $source
     */
    public function picture(Environment $twig, array $source): string
    {
        if ($source['src'] === null) {
            return '';
        }

        $variants = $this->imageRepository->getVariants(
            $this->sourceFactory->createSource($source['src'], $source['actions'])
        );

        return $twig->render('media/picture.html.twig', [ 'variants' => $variants ]);
    }

    /**
     * @param array<array{src: string, caption?: string}> $sources
     */
    public function passthrough(
        Environment $twig,
        array $sources,
        ?string $classes = null,
        ?string $description = null
    ): string {
        $images = array_map(function (array $s): array {
            return [
                'src' => $this->fileHandler->getSourceUrl(new Source($s['src'], [])),
                'caption' => $s['caption'] ?? null,
            ];
        }, $sources);

        return $twig->render('media/image-row.html.twig', [
            'images' => $images,
            'classes' => $classes,
            'description' => $description,
        ]);
    }

    /**
     * @param array<array{src: string, type: ?string}> $sources
     */
    public function video(
        Environment $twig,
        array $sources,
        ?string $poster = null,
        float $ratio = 16 / 9,
        ?string $subtitles = null,
        ?string $description = null,
    ): string {
        if ($poster !== null) {
            $posterVariant = $this->imageRepository->getVariants(
                $this->sourceFactory->createSource($poster, [ 'resize:poster' ]),
                [ MimeType::JPEG ]
            );
            $poster = array_first($posterVariant)?->src;
        }

        $s = array_map(function (array $s): array {
            return [
                'src' => $this->fileHandler->getSourceUrl($this->sourceFactory->createSource($s['src'], [])),
                'type' => $s['type'],
            ];
        }, $sources);

        $video = new readonly class ($s, $poster, $ratio, $subtitles) {
            /**
             * @param array<array{src: string, type: ?string}> $sources
             */
            public function __construct(
                public array $sources,
                public ?string $poster,
                public float $ratio,
                public ?string $subtitles
            ) {
            }
        };

        return $twig->render('media/video.html.twig', [
            'video' => $video,
            'description' => $description,
        ]);
    }

    /**
     * @param array<QuizQuestion> $questions
     */
    public function quiz(Environment $twig, array $questions): string
    {
        foreach ($questions as &$question) {
            $source = $this->sourceFactory->createSource($question['image']['src'], $question['image']['actions']);
            $question['images'] = $this->imageRepository->getVariants($source);
            unset($question['image']);
        }

        return $twig->render('media/quiz.html.twig', [ 'quiz' => $questions ]);
    }

    /**
     * @param array<MapPoint> $points
     * @param array<MapRoute> $routes
     * @param array{0: float, 1: float}|null $centre
     */
    public function map(
        Environment $twig,
        array $points = [],
        array $routes = [],
        ?array $centre = null,
        ?int $zoom = null
    ): string {
        return $twig->render('media/map.html.twig', [
            'id' => ByteString::fromRandom(16),
            'points' => $points,
            'routes' => $routes,
            'centre' => $centre,
            'zoom' => $zoom,
        ]);
    }

    public function imageType(string $src): string
    {
        return $this->getImageSize($src)['mime'] ?? '';
    }

    public function imageWidth(string $src): int
    {
        return $this->getImageSize($src)[0] ?? 0;
    }

    public function imageHeight(string $src): int
    {
        return $this->getImageSize($src)[1] ?? 0;
    }

    /**
     * @return array{0?: int, 1?: int, mime?: string}
     */
    private function getImageSize(string $src): array
    {
        if (!array_key_exists($src, $this->imageSizes)) {
            $path = $this->fileHandler->getSourcePath(new Source($src, []));
            $imageSize = (file_exists($path)) ? getimagesize($path) : [];
            $imageSize = ($imageSize === false) ? [] : $imageSize;

            $this->imageSizes[$src] = $imageSize;
        }

        return $this->imageSizes[$src];
    }
}
