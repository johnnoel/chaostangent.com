<?php

declare(strict_types=1);

namespace App\Command;

use App\Post\Processor;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\DTO\PostDTO;
use App\Repository\PostRepository;
use Exception;
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
            ->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch')
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

    private function getPostCount(mixed $alias): int
    {
        if (is_string($alias)) {
            return 1;
        }

        return $this->postRepository->countFilteredPosts(new FilterPostsCriteria());
    }

    /**
     * @return Collection<int,PostDTO>
     */
    private function getPosts(mixed $alias, int $page): Collection
    {
        if (is_string($alias)) {
            $post = $this->postRepository->findOneBy([ 'alias' => $alias ]);

            if ($post === null) {
                throw new Exception('Unable to find post with alias ' . $alias);
            }

            return new Collection([ new PostDTO($post) ]);
        }

        return $this->postRepository->filterPosts(new FilterPostsCriteria(page: $page, perPage: self::PER_PAGE));
    }
}
