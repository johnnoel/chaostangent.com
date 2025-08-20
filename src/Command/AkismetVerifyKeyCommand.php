<?php

declare(strict_types=1);

namespace App\Command;

use App\Comment\AkismetClient;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'akismet:verify-key',
    description: 'Verify the currently set Akismet key',
)]
class AkismetVerifyKeyCommand extends Command
{
    public function __construct(private readonly AkismetClient $akismetClient)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->akismetClient->verifyKey();
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Akismet key successfully verified');

        return Command::SUCCESS;
    }
}
