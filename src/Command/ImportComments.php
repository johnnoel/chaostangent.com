<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Symfony\Component\String\s;

#[AsCommand(
    name: 'app:import-comments',
    description: 'Import comments from a CSV file',
)]
class ImportComments extends Command
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('csv', InputArgument::REQUIRED, 'CSV file to import comments from');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $csvFile */
        $csvFile = $input->getArgument('csv');

        if (!file_exists($csvFile) || !is_readable($csvFile)) {
            $io->error('Unable to read CSV file: ' . $csvFile);

            return Command::FAILURE;
        }

        $posts = [];

        $fh = fopen($csvFile, 'r');
        if ($fh === false) {
            $io->error('Unable to open CSV file: ' . $csvFile);

            return Command::FAILURE;
        }
        fgetcsv($fh, 16384); // headers

        while (!feof($fh)) {
            $r = fgetcsv($fh, 16384);
            if ($r === false || count($r) !== 8) {
                continue;
            }

            $alias = $r[0];

            $post = $posts[$alias] ?? $this->postRepository->findOneBy([ 'alias' => $alias ]);
            $posts[$alias] = $post;

            if (!($post instanceof Post)) {
                $io->warning('Post not found: ' . $alias);
                continue;
            }

            $comment = new Comment(
                $post,
                comment: strval($r[7]),
                authorName: strval($r[2]),
                authorEmail: strval($r[3]),
                authorIp: strval($r[5]),
                approved: true,
                spam: false,
                authorUrl: (($r[4] === null || $r[4] === '') ? null : $r[4]),
            );

            $this->commentRepository->create($comment);
            $io->success(sprintf(
                'Imported comment [%s] %s %s',
                $alias,
                $comment->getAuthorName(),
                s($comment->getComment())->truncate(128)
            ));
        }

        return Command::SUCCESS;
    }
}
