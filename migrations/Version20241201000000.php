<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add adoption journey features: bonding level, preferred interaction, fostering, and compatibility score';
    }

    public function up(Schema $schema): void
    {
        // Add bonding level column (default 0)
        $this->addSql('ALTER TABLE cat ADD bonding_level INT NOT NULL DEFAULT 0');

        // Add preferred interaction column (default "pet")
        $this->addSql('ALTER TABLE cat ADD preferred_interaction VARCHAR(20) NOT NULL DEFAULT \'pet\'');

        // Add fostering columns
        $this->addSql('ALTER TABLE cat ADD fostered TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE cat ADD fostered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Add compatibility score column (nullable)
        $this->addSql('ALTER TABLE cat ADD compatibility_score INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat DROP bonding_level');
        $this->addSql('ALTER TABLE cat DROP preferred_interaction');
        $this->addSql('ALTER TABLE cat DROP fostered');
        $this->addSql('ALTER TABLE cat DROP fostered_at');
        $this->addSql('ALTER TABLE cat DROP compatibility_score');
    }
}
