<?php

declare(strict_types=1);

namespace App\Command;

use App\Extension\Twig\MediaExtension;
use App\Image\GatheringImageRepository;
use App\Image\SourceFactory;
use App\Message\ProcessImage;
use App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

#[AsCommand(
    name: 'app:generate-images',
    description: 'Generate images from posts',
)]
class GenerateImagesCommand extends Command
{
    use GetsPosts;

    private const int PER_PAGE = 50;

    public function __construct(
        private readonly MediaExtension $imageExtension,
        private readonly GatheringImageRepository $imageRepository,
        private readonly SourceFactory $sourceFactory,
        private readonly MessageBusInterface $messageBus,
        private readonly Environment $twig,
        PostRepository $postRepository
    ) {
        parent::__construct();

        $this->postRepository = $postRepository;
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Don\'t generate any images')
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Always (re)generate images')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $alias = $input->getOption('alias');
        $postCount = $this->getPostCount($alias);
        $pages = ceil($postCount / self::PER_PAGE);
        $postSources = [];
        $sourceCount = 0;

        $this->imageExtension->setImageRepository($this->imageRepository);

        $progressBar = $io->createProgressBar($postCount);
        $progressBar->setFormat("%message%\n%current%/%max% %bar% %percent:3s%%");
        $progressBar->setMessage('Processing posts...');
        $progressBar->start();

        for ($page = 1; $page <= $pages; $page++) {
            foreach ($this->getPosts($alias, $page) as $dto) {
                $post = $dto->post;
                $template = $this->twig->createTemplate($post->getSummary() . $post->getContent(), $post->getAlias());
                $template->render();
                $sources = [];

                if (is_array($post->getImage()) && $post->getImage()['src'] !== null) {
                    $sources[] = $this->sourceFactory->createSource(...$post->getImage());
                }

                if (count($sources) === 0) {
                    continue;
                }

                $sources = array_unique($sources);
                $postSources[] = [ $post, $sources ];
                $sourceCount += count($sources);
                $this->imageRepository->reset();
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $progressBar->start($sourceCount);
        $dryRun = boolval($input->getOption('dry-run'));
        $force = boolval($input->getOption('force'));

        foreach ($postSources as [ $post, $sources ]) {
            $progressBar->setMessage($post->getAlias());

            foreach ($sources as $source) {
                if (!$dryRun) {
                    try {
                        $this->messageBus->dispatch(new ProcessImage($source, $force));
                    } catch (\Exception $e) {
                        $io->error('Could not process source "' . $source->src . '": ' . $e->getMessage());
                    }
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
