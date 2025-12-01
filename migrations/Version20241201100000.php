<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241201100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI-generated content fields for caching personality profiles, backstories, and fun facts';
    }

    public function up(Schema $schema): void
    {
        // Add AI personality profile (TEXT for longer content)
        $this->addSql('ALTER TABLE cat ADD ai_personality_profile LONGTEXT DEFAULT NULL');

        // Add AI backstory (TEXT for longer content)
        $this->addSql('ALTER TABLE cat ADD ai_backstory LONGTEXT DEFAULT NULL');

        // Add AI fun facts (JSON array)
        $this->addSql('ALTER TABLE cat ADD ai_fun_facts JSON DEFAULT NULL');

        // Add timestamp for when AI content was generated
        $this->addSql('ALTER TABLE cat ADD ai_generated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat DROP ai_personality_profile');
        $this->addSql('ALTER TABLE cat DROP ai_backstory');
        $this->addSql('ALTER TABLE cat DROP ai_fun_facts');
        $this->addSql('ALTER TABLE cat DROP ai_generated_at');
    }
}
