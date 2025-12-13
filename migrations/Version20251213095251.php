<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213095251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tweets username';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tweets ADD username VARCHAR(16) DEFAULT \'_ceetea\' NOT NULL');
        $this->addSql('UPDATE tweets SET username = ? WHERE created_at <= ?', [
            'chaostangent',
            '2020-08-08 21:12:32',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tweets DROP username');
    }
}
