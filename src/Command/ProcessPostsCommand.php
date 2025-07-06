<?php

declare(strict_types=1);

namespace App\Command;

use App\Post\Processor;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:process-posts',
    description: 'Iterate through all posts and process them according to arguments',
)]
class ProcessPostsCommand extends Command
{
    private const PER_PAGE = 50;

    /**
     * @param iterable<Processor> $processors
     */
    public function __construct(
        private readonly PostRepository $postRepository,
        #[AutowireIterator('app.post_processor')]
        private readonly iterable $processors
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('processors', InputArgument::IS_ARRAY, 'Processors to apply to posts')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Don\'t commit any changes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $processors = $input->getArgument('processors');

        if (!is_array($processors) || count($processors) === 0) {
            $io->error('No processors supplied');

            return Command::FAILURE;
        }

        $toApply = array_filter(
            iterator_to_array($this->processors),
            fn (Processor $p): bool => in_array($p->getSlug(), $processors)
        );

        $criteria = new FilterPostsCriteria();
        $postCount = $this->postRepository->countFilteredPosts($criteria);
        $pages = ceil($postCount / self::PER_PAGE);

        $dryRun = boolval($input->getOption('dry-run'));
        $progressBar = new ProgressBar($output, $postCount);
        $progressBar->start();

        for ($page = 1; $page <= $pages; $page++) {
            $posts = $this->postRepository->filterPosts($criteria);

            // devtodo parallelise
            foreach ($posts as $post) {
                foreach ($toApply as $p) {
                    $p->process($post);
                }

                if (!$dryRun) {
                    $this->postRepository->update($post);
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
