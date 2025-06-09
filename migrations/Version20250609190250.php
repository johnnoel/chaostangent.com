<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250609190250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial table setup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categories (id UUID NOT NULL, parent_id UUID DEFAULT NULL, title VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3AF34668727ACA70 ON categories (parent_id)');
        $this->addSql('CREATE UNIQUE INDEX category_alias_unique ON categories (alias)');
        $this->addSql('CREATE TABLE comments (id UUID NOT NULL, parent_id UUID DEFAULT NULL, post_id UUID NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, comment TEXT NOT NULL, author_name VARCHAR(255) NOT NULL, author_email VARCHAR(255) NOT NULL, author_url TEXT DEFAULT NULL, author_ip inet NOT NULL, approved BOOLEAN DEFAULT \'false\' NOT NULL, spam BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F9E962A727ACA70 ON comments (parent_id)');
        $this->addSql('CREATE INDEX IDX_5F9E962A4B89032C ON comments (post_id)');
        $this->addSql('COMMENT ON COLUMN comments.created IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE posts (id UUID NOT NULL, title VARCHAR(255) NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, alias VARCHAR(255) NOT NULL, content TEXT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, published BOOLEAN DEFAULT \'false\' NOT NULL, extra JSONB NOT NULL, commonmark TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX post_alias_index ON posts (alias)');
        $this->addSql('CREATE INDEX post_alias_date ON posts (date)');
        $this->addSql('COMMENT ON COLUMN posts.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN posts.created IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN posts.updated IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE posts2categories (post_id UUID NOT NULL, category_id UUID NOT NULL, PRIMARY KEY(post_id, category_id))');
        $this->addSql('CREATE INDEX IDX_338371794B89032C ON posts2categories (post_id)');
        $this->addSql('CREATE INDEX IDX_3383717912469DE2 ON posts2categories (category_id)');
        $this->addSql('CREATE TABLE posts2tags (post_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(post_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_B44E1E634B89032C ON posts2tags (post_id)');
        $this->addSql('CREATE INDEX IDX_B44E1E63BAD26311 ON posts2tags (tag_id)');
        $this->addSql('CREATE TABLE tags (id UUID NOT NULL, tag VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX tag_alias_unique ON tags (tag)');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A727ACA70 FOREIGN KEY (parent_id) REFERENCES comments (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts2categories ADD CONSTRAINT FK_338371794B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts2categories ADD CONSTRAINT FK_3383717912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts2tags ADD CONSTRAINT FK_B44E1E634B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts2tags ADD CONSTRAINT FK_B44E1E63BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE kudos (id UUID NOT NULL, post_id UUID NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip inet NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1096D5FD4B89032C ON kudos (post_id)');
        $this->addSql('COMMENT ON COLUMN kudos.created IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE kudos ADD CONSTRAINT FK_1096D5FD4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories DROP CONSTRAINT FK_3AF34668727ACA70');
        $this->addSql('ALTER TABLE posts2categories DROP CONSTRAINT FK_3383717912469DE2');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A727ACA70');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A4B89032C');
        $this->addSql('ALTER TABLE posts2categories DROP CONSTRAINT FK_338371794B89032C');
        $this->addSql('ALTER TABLE posts2tags DROP CONSTRAINT FK_B44E1E634B89032C');
        $this->addSql('ALTER TABLE posts2tags DROP CONSTRAINT FK_B44E1E63BAD26311');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE kudos');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE posts2categories');
        $this->addSql('DROP TABLE posts2tags');
        $this->addSql('DROP TABLE tags');
    }
}
