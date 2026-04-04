<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404081331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use pg_textsearch for searching posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS posts_searchable_id');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_textsearch');
        $this->addSql('CREATE INDEX posts_search_idx ON posts USING bm25(searchable) WITH (text_config=\'english\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS posts_search_idx');
        $this->addSql('DROP EXTENSION IF EXISTS pg_textsearch');
        $this->addSql('CREATE INDEX posts_searchable_id ON posts USING GIN ((to_tsvector(\'english\', searchable)))');
    }
}
