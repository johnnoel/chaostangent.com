<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822162527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop post commonmark column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP commonmark');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD commonmark TEXT DEFAULT NULL');
    }
}
