<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Post;
use App\Entity\Tweet;
use App\Extension\Twig\MediaExtension;
use App\Image\GatheringImageRepository;
use App\Image\Source;
use App\Image\SourceFactory;
use App\Message\ProcessImage;
use App\Repository\PostRepository;
use App\Repository\TweetRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

#[AsCommand(
    name: 'app:generate-images',
    description: 'Generate images from posts and tweets',
)]
class GenerateImagesCommand extends Command
{
    use GetsPosts;

    private const int PER_PAGE = 50;

    public function __construct(
        private readonly TweetRepository $tweetRepository,
        private readonly MediaExtension $mediaExtension,
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
            ->addOption('tweets-only', null, InputOption::VALUE_NONE, 'Generate images from tweets only')
            ->addOption('posts-only', null, InputOption::VALUE_NONE, 'Generate images from posts only')
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Always (re)generate images')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $alias = $input->getOption('alias');

        $this->mediaExtension->setImageRepository($this->imageRepository);

        $progressBar = $io->createProgressBar();
        $progressBar->setFormat("%message%\n%current%/%max% %bar% %percent:3s%%");

        $postSources = (!$input->getOption('tweets-only')) ? $this->getPostSources($alias, $progressBar) : [];
        $tweetSources = (!$input->getOption('posts-only')) ? $this->getTweetSources($progressBar) : [];

        $progressBar->start(count($postSources) + count($tweetSources));
        $dryRun = boolval($input->getOption('dry-run'));
        $force = boolval($input->getOption('force'));

        foreach ([ $postSources, $tweetSources ] as $sourceList) {
            foreach ($sourceList as [ $obj, $sources ]) {
                $progressBar->setMessage(($obj instanceof Post) ? $obj->getAlias() : $obj->getId());

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
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }

    /**
     * @return array<array{0: Post, 1: array<Source>}>
     */
    private function getPostSources(mixed $alias, ProgressBar $progressBar): array
    {
        $postCount = $this->getPostCount($alias);
        $pages = ceil($postCount / self::PER_PAGE);
        $postSources = [];

        $progressBar->setMessage('Processing posts...');
        $progressBar->start($postCount);

        for ($page = 1; $page <= $pages; $page++) {
            foreach ($this->getPosts($alias, $page) as $dto) {
                $post = $dto->post;
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

                $sources = array_unique($sources);
                $postSources[] = [ $post, $sources ];
                $this->imageRepository->reset();
            }
        }

        $progressBar->finish();

        return $postSources;
    }

    /**
     * @return array<array{0: Tweet, 1: array<Source>}>
     */
    private function getTweetSources(ProgressBar $progressBar): array
    {
        $tweetCount = $this->tweetRepository->count();
        $pages = ceil($tweetCount / self::PER_PAGE);
        $tweetSources = [];

        $progressBar->setMessage('Processing tweets...');
        $progressBar->start($tweetCount);

        for ($page = 1; $page <= $pages; $page++) {
            $tweets = $this->tweetRepository->findBy(
                criteria: [],
                orderBy: [ 'createdAt' => 'DESC' ],
                limit: self::PER_PAGE,
                offset: ($page - 1) * self::PER_PAGE
            );
            foreach ($tweets as $tweet) {
                $template = $this->twig->load('tweets/_tweet.html.twig');
                $template->render([ 'tweet' => $tweet ]);
                $sources = $this->imageRepository->sources;

                $progressBar->advance();

                if (count($sources) === 0) {
                    continue;
                }

                $tweetSources[] = [ $tweet, $sources ];
                $this->imageRepository->reset();
            }
        }

        $progressBar->finish();

        return $tweetSources;
    }
}
