<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819120258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add searchable to posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD searchable TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX posts_searchable_id ON posts USING GIN ((to_tsvector(\'english\', searchable)))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS posts_searchable_idx');
        $this->addSql('ALTER TABLE posts DROP searchable');
    }
}
