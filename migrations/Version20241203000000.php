<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cafe presence tracking fields to cat table.
 * Cats can now arrive and leave the cafe dynamically.
 */
final class Version20241203000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add in_cafe, last_visit_at, and left_cafe_at columns to cat table for cafe presence scheduling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat ADD in_cafe TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE cat ADD last_visit_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE cat ADD left_cafe_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat DROP in_cafe');
        $this->addSql('ALTER TABLE cat DROP last_visit_at');
        $this->addSql('ALTER TABLE cat DROP left_cafe_at');
    }
}
