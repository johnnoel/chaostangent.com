<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Tag;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tag:merge',
    description: 'Merge one tag into another',
)]
class MergeTagCommand extends Command
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly PostRepository $postRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'The tag that will be merged (won\'t exist afterwards)')
            ->addArgument('target', InputArgument::REQUIRED, 'The tag that will be merged into (will exist afterwards)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $source */
        $source = $input->getArgument('source');
        /** @var string $target */
        $target = $input->getArgument('target');

        $sourceTag = $this->tagRepository->findOneBy([ 'alias' => $source ]);
        $targetTag = $this->tagRepository->findOneBy([ 'alias' => $target ]);

        if (!($sourceTag instanceof Tag)) {
            $io->error(sprintf('The tag "%s" does not exist', $source));

            return Command::FAILURE;
        }

        if (!($targetTag instanceof Tag)) {
            $io->error(sprintf('The tag "%s" does not exist', $target));

            return Command::FAILURE;
        }

        $posts = $sourceTag->getPosts();
        $progressBar = $io->createProgressBar(count($posts));

        foreach ($posts as $post) {
            $currentTags = $post->getTags();
            if (!$currentTags->contains($targetTag)) {
                $currentTags->add($targetTag);
            }

            $currentTags->removeElement($sourceTag);
            $this->postRepository->update($post);

            $progressBar->advance();
        }

        $this->tagRepository->delete($sourceTag);
        $progressBar->finish();

        return Command::SUCCESS;
    }
}
