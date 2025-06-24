<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250624184114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Novel indexes on posts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE posts ADD CONSTRAINT check_date_when_published CHECK ((published = true AND date IS NOT NULL) OR (published = false))
        SQL);
        $this->addSql(<<<SQL
            CREATE INDEX posts_year_idx ON posts (EXTRACT('YEAR' FROM date))
        SQL);
        $this->addSql(<<<SQL
            CREATE INDEX posts_month_idx ON posts (EXTRACT('MONTH' FROM date))
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE posts DROP CONSTRAINT check_date_when_published
        SQL);
        $this->addSql(<<<SQL
            DROP INDEX posts_year_idx
        SQL);
        $this->addSql(<<<SQL
            DROP INDEX posts_month_idx
        SQL);
    }
}
