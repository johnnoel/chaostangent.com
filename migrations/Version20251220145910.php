<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220145910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create webmentions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webmentions (id UUID NOT NULL, source TEXT NOT NULL, ip inet, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, post_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_62DE3CBC4B89032C ON webmentions (post_id)');
        $this->addSql('ALTER TABLE webmentions ADD CONSTRAINT FK_62DE3CBC4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webmentions DROP CONSTRAINT FK_62DE3CBC4B89032C');
        $this->addSql('DROP TABLE webmentions');
    }
}
