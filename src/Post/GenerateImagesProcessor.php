<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use App\Extension\Twig\ImageExtension;
use App\Image\ProcessingImageRepository;
use Twig\Environment;

readonly final class GenerateImagesProcessor implements Processor
{
    public function __construct(
        private ImageExtension $imageExtension,
        private ProcessingImageRepository $imageRepository,
        private Environment $twig
    ) {
    }

    public function process(Post $post): void
    {
        $this->imageExtension->setImageRepository($this->imageRepository);

        $template = $this->twig->createTemplate($post->getContent(), $post->getAlias());
        $template->render();
    }

    public function getSlug(): string
    {
        return 'generate-images';
    }
}
