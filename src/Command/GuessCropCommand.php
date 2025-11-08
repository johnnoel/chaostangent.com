<?php

declare(strict_types=1);

namespace App\Command;

use App\Image\CropGuesser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\HandleTrait;

#[AsCommand(name: 'app:guess-crop', description: 'Guess the crop of an image')]
class GuessCropCommand extends Command
{
    use HandleTrait;

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'The source, uncropped image')
            ->addArgument('cropped', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The cropped image(s)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');
        /** @var array<mixed> $cropped */
        $cropped = $input->getArgument('cropped');

        if (!is_string($source) || !is_file($source)) {
            $io->error('Unable to read source file');

            return Command::FAILURE;
        }

        $cropGuesser = new CropGuesser();

        foreach ($cropped as $crop) {
            if (!is_string($crop) || !is_file($crop)) {
                $io->error('Unable to read cropped image: ' . var_export($crop, true));

                continue;
            }

            $c = $cropGuesser->guessCrop($source, $crop);
            $io->success(sprintf('%s => %dx%d+%d+%d', $crop, $c['w'], $c['h'], $c['x'], $c['y']));
        }

        return Command::SUCCESS;
    }
}
