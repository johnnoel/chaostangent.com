<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\ImportTweet;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:import-tweets', description: 'Import tweets from an export JSON file')]
class ImportTweets extends Command
{
    use HandleTrait;

    private const int CLEAR_DOCTRINE_AFTER = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus
    ) {
        parent::__construct();

        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'File to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        if (!is_string($file) || !file_exists($file)) {
            $io->error('Unable to read file ' . ((is_string($file)) ? $file : ''));

            return Command::FAILURE;
        }

        try {
            $json = json_decode(strval(file_get_contents($file)), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!is_iterable($json)) {
            $io->error('Invalid JSON');

            return Command::FAILURE;
        }

        $count = 0;
        $progressBar = $io->createProgressBar(count($json));

        /** @var array<mixed> $tweet */
        foreach ($json as $tweet) {
            $this->handle(new ImportTweet($tweet));

            if ($count++ >= self::CLEAR_DOCTRINE_AFTER) {
                $this->entityManager->clear();
                $count = 0;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
