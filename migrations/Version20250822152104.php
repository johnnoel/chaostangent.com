<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250822152104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post image';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts ADD image JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP image');
    }
}
