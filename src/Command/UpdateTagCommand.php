<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tag:update',
    description: 'Update a tag',
)]
class UpdateTagCommand extends Command
{
    public function __construct(private readonly TagRepository $tagRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('alias', InputArgument::REQUIRED, 'Alias of the tag to update')
            ->addOption('tag', mode: InputOption::VALUE_REQUIRED, description: 'New tag name')
            ->addOption('alias', mode: InputOption::VALUE_REQUIRED, description: 'New tag alias')
            ->addOption('delete', mode: InputOption::VALUE_NONE, description: 'Delete the tag')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $alias */
        $alias = $input->getArgument('alias');
        $tag = $this->tagRepository->findOneBy([ 'alias' => $alias ]);

        if (!($tag instanceof Tag)) {
            $io->error(sprintf('The tag "%s" does not exist', $alias));

            return Command::FAILURE;
        }

        if ($input->getOption('delete')) {
            $this->tagRepository->delete($tag);
            $io->success(sprintf('The tag "%s" was successfully deleted', $alias));

            return Command::SUCCESS;
        }

        /** @var string|null $newTag */
        $newTag = $input->getOption('tag');
        /** @var string|null $newAlias */
        $newAlias = $input->getOption('alias');

        $tag->setTag($newTag ?? $tag->getTag());
        $tag->setAlias($newAlias ?? $tag->getAlias());
        $this->tagRepository->update($tag);

        $io->success(sprintf('The tag "%s" was successfully updated', $alias));

        return Command::SUCCESS;
    }
}
