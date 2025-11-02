<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Post;
use App\Message\ImportPost;
use DirectoryIterator;
use Spatie\Watcher\Watch;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:import-posts',
    description: 'Import posts from a directory',
)]
class ImportPostsCommand extends Command
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        parent::__construct();

        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->addArgument('location', InputArgument::REQUIRED, 'Where to import from')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch your file / directory for changes')
            ->addOption('generate-images', 'g', InputOption::VALUE_NONE, 'Generate images for each post imported')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $location = $input->getArgument('location');

        if (!is_string($location) || !is_readable($location) || (!is_file($location) && !is_dir($location))) {
            $io->error('Invalid location');

            return Command::FAILURE;
        }

        $files = [ new SplFileInfo($location) ];

        if (is_dir($location)) {
            $files = new DirectoryIterator($location);
        }

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $io->info($file->getFilename());
            /** @var Post $post */
            $post = $this->handle(new ImportPost(strval(file_get_contents($file->getPathname()))));

            if ($input->getOption('generate-images')) {
                $cmdInput = new ArrayInput([
                    'command' => 'app:generate-images',
                    '--force' => true,
                    '--alias' => $post->getAlias(),
                ]);

                $cmdInput->setInteractive(false);
                $this->getApplication()?->doRun($cmdInput, $output);
            }
        }

        if ($input->getOption('watch')) {
            $io->info('Watching...');
            Watch::path($location)
                ->onFileCreated(function (string $path) use ($io): void {
                    $io->info('Importing ' . $path);
                    $this->handle(new ImportPost(strval(file_get_contents($path))));
                    // devtodo now generate images
                })
                ->onFileUpdated(function (string $path) use ($io): void {
                    $io->info('Importing ' . $path);
                    $this->handle(new ImportPost(strval(file_get_contents($path))));
                })
                ->start()
            ;
        }

        return Command::SUCCESS;
    }
}
