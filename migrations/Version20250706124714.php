<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706124714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add summary to posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD summary TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP summary');
    }
}
