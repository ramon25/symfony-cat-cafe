<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user-specific cat bonding system with cat_bonding table';
    }

    public function up(Schema $schema): void
    {
        // Create cat_bonding table
        $this->addSql('CREATE TABLE cat_bonding (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            cat_id INT NOT NULL,
            bonding_level INT NOT NULL DEFAULT 0,
            compatibility_score INT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CAT_BONDING_USER (user_id),
            INDEX IDX_CAT_BONDING_CAT (cat_id),
            UNIQUE INDEX unique_user_cat_bonding (user_id, cat_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys
        $this->addSql('ALTER TABLE cat_bonding ADD CONSTRAINT FK_CAT_BONDING_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cat_bonding ADD CONSTRAINT FK_CAT_BONDING_CAT FOREIGN KEY (cat_id) REFERENCES cat (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Remove cat_bonding table
        $this->addSql('ALTER TABLE cat_bonding DROP FOREIGN KEY FK_CAT_BONDING_USER');
        $this->addSql('ALTER TABLE cat_bonding DROP FOREIGN KEY FK_CAT_BONDING_CAT');
        $this->addSql('DROP TABLE cat_bonding');
    }
}
