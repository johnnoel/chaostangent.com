<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Symfony\Component\String\s;

#[AsCommand(
    name: 'app:export-wordpress-comments',
    description: 'Export Wordpress comments to a CSV file'
)]
class ExportWordpressComments extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'host',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The Wordpress database host',
            default: 'localhost'
        )->addOption(
            'port',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The Wordpress database port',
            default: '3306'
        )->addOption(
            'database',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The Wordpress database',
            default: 'wordpress'
        )->addOption(
            'username',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The Wordpress database username',
            default: 'wordpress'
        )->addOption(
            'password',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The Wordpress database password',
            default: 'wordpress'
        )->addOption(
            'format',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Output format',
            default: 'table'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $host */
        $host = $input->getOption('host');
        /** @var string $port */
        $port = $input->getOption('port');
        /** @var string $database */
        $database = $input->getOption('database');
        /** @var string $username */
        $username = $input->getOption('username');
        /** @var string $password */
        $password = $input->getOption('password');
        /** @var string $format */
        $format = $input->getOption('format');

        $source = DriverManager::getConnection([
            'dbname' => $database,
            'host' => $host,
            'port' => intval($port),
            'user' => $username,
            'password' => $password,
            'driver' => 'pdo_mysql',
        ]);

        $stmt = $source->executeQuery(<<<SQL
            SELECT c.*, p.post_name
            FROM wp_comments c
            JOIN wp_posts p ON c.comment_post_ID = p.ID
            WHERE c.comment_approved = :approved
            ORDER BY c.comment_date_gmt ASC
        SQL, [ 'approved' => '1' ], [ 'approved' => Types::STRING ]);

        if ($stmt->rowCount() === 0) {
            $io->error('Could not find any comments');

            return Command::FAILURE;
        }

        /**
         * @var array<array{
         *     comment_ID: string,
         *     comment_author: string,
         *     comment_author_email: string,
         *     comment_author_url: string,
         *     comment_author_IP: string,
         *     comment_date_gmt: string,
         *     comment_content: string,
         *     comment_agent: string,
         *     post_name: string,
         * }> $res
         */
        $res = $stmt->fetchAllAssociative();

        if ($format === 'csv') {
            $fh = fopen('php://stdout', 'w');
            if ($fh === false) {
                $io->error('Could not open stdout for writing');

                return Command::FAILURE;
            }

            fputcsv($fh, [ 'post_name', 'date', 'author', 'email', 'url', 'ip', 'agent', 'comment' ]);

            foreach ($res as $r) {
                fputcsv($fh, [
                    $r['post_name'],
                    $r['comment_date_gmt'],
                    $r['comment_author'],
                    $r['comment_author_email'],
                    $r['comment_author_url'],
                    $r['comment_author_IP'],
                    $r['comment_agent'],
                    $r['comment_content'],
                ]);
            }

            fflush($fh);
            fclose($fh);

            return Command::SUCCESS;
        }

        $rows = array_map(function (array $r): array {
            return [
                $r['post_name'],
                $r['comment_date_gmt'],
                $r['comment_author'],
                $r['comment_author_email'],
                s($r['comment_content'])->truncate(128, '...'),
            ];
        }, $res);

        $io->table([ 'id', 'date', 'name', 'email', 'comment' ], $rows);

        return Command::SUCCESS;
    }
}
