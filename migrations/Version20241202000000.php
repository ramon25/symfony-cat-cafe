<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI-generated website fields for storing cat personal websites';
    }

    public function up(Schema $schema): void
    {
        // Add AI website HTML content (LONGTEXT for full HTML pages)
        $this->addSql('ALTER TABLE cat ADD ai_website_html LONGTEXT DEFAULT NULL');

        // Add AI website layout type (to track which random layout was used)
        $this->addSql('ALTER TABLE cat ADD ai_website_layout VARCHAR(50) DEFAULT NULL');

        // Add timestamp for when AI website was generated
        $this->addSql('ALTER TABLE cat ADD ai_website_generated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat DROP ai_website_html');
        $this->addSql('ALTER TABLE cat DROP ai_website_layout');
        $this->addSql('ALTER TABLE cat DROP ai_website_generated_at');
    }
}
