<?php

declare(strict_types=1);

namespace App\Command;

use App\Extension\Twig\MediaExtension;
use App\Image\FileHandler;
use App\Image\GatheringImageRepository;
use App\Image\MimeType;
use App\Image\Source;
use App\Image\SourceFactory;
use App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand(
    name: 'app:list-images',
    description: 'List image paths',
)]
class ListImagesCommand extends Command
{
    use GetsPosts;

    private const int PER_PAGE = 50;

    public function __construct(
        private readonly MediaExtension $mediaExtension,
        private readonly GatheringImageRepository $imageRepository,
        private readonly SourceFactory $sourceFactory,
        private readonly FileHandler $fileHandler,
        private readonly Environment $twig,
        PostRepository $postRepository
    ) {
        parent::__construct();

        $this->postRepository = $postRepository;
    }

    protected function configure(): void
    {
        $this->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $alias = $input->getOption('alias');

        $this->mediaExtension->setImageRepository($this->imageRepository);

        $postCount = $this->getPostCount($alias);
        $pages = ceil($postCount / self::PER_PAGE);

        $progressBar = $io->createProgressBar($postCount);
        $progressBar->setFormat("%message%\n%current%/%max% %bar% %percent:3s%%");

        /** @var list<string> $postVariants */
        $postVariants = [];

        for ($page = 1; $page <= $pages; $page++) {
            foreach ($this->getPosts($alias, $page) as $dto) {
                $post = $dto->post;
                $progressBar->setMessage($post->getAlias());
                $template = $this->twig->createTemplate($post->getSummary() . $post->getContent(), $post->getAlias());
                $template->render();
                $sources = $this->imageRepository->sources;

                if (is_array($post->getImage()) && $post->getImage()['src'] !== null) {
                    $sources[] = $this->sourceFactory->createSource(...$post->getImage());
                }

                $progressBar->advance();

                if (count($sources) === 0) {
                    continue;
                }

                $variants = [];
                foreach ([ MimeType::AVIF, MimeType::WEBP, MimeType::JPEG ] as $mimeType) {
                    $variants = array_merge($variants, array_map(
                        fn (Source $s): string => $this->fileHandler->getVariantPath($s, $mimeType),
                        $sources
                    ));
                }

                $variants = array_unique($variants);
                $postVariants = array_merge($postVariants, $variants);

                $this->imageRepository->reset();
            }
        }

        $progressBar->finish();
        $progressBar->clear();

        sort($postVariants);
        foreach ($postVariants as $postVariant) {
            $output->writeln($postVariant);
        }

        return Command::SUCCESS;
    }
}
