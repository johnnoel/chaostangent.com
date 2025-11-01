<?php

declare(strict_types=1);

namespace App\Command;

use App\Post\Processor;
use App\Repository\PostRepository;
use Illuminate\Support\Collection;
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
    use GetsPosts;

    private const int PER_PAGE = 50;

    /**
     * @param iterable<Processor> $processors
     */
    public function __construct(
        PostRepository $postRepository,
        #[AutowireIterator('app.post_processor')]
        private readonly iterable $processors
    )
    {
        parent::__construct();

        $this->postRepository = $postRepository;
    }

    protected function configure(): void
    {
        $this->addArgument('processors', InputArgument::IS_ARRAY, 'Processors to apply to posts')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Don\'t commit any changes')
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $processors = $input->getArgument('processors');

        if (!is_array($processors) || count($processors) === 0) {
            $io->error('No processors supplied');

            return Command::FAILURE;
        }

        $availableProcessors = (new Collection($this->processors))->keyBy(fn(Processor $p): string => $p->getSlug());
        $toApply = array_map(fn(string $p): ?Processor => $availableProcessors[$p] ?? null, $processors);

        $postCount = $this->getPostCount($input->getOption('alias'));
        $pages = ceil($postCount / self::PER_PAGE);

        $dryRun = boolval($input->getOption('dry-run'));
        $progressBar = new ProgressBar($output, $postCount);
        $progressBar->start();

        for ($page = 1; $page <= $pages; $page++) {
            $posts = $this->getPosts($input->getOption('alias'), $page);

            // devtodo parallelise
            foreach ($posts as $p) {
                foreach ($toApply as $a) {
                    $a->process($p->post);
                }

                if (!$dryRun) {
                    $this->postRepository->update($p->post);
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
