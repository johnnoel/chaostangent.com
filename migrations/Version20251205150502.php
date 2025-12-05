<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251205150502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tweet repository';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tweets (id VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, full_text TEXT NOT NULL, original JSONB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX tweet_createdat_index ON tweets (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tweets');
    }
}
