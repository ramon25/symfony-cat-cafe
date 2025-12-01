<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241201200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add multiuser system with user accounts, authentication, and user-owned achievements';
    }

    public function up(Schema $schema): void
    {
        // Create user table
        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            username VARCHAR(50) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            achievement_stats JSON DEFAULT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create user_achievement table
        $this->addSql('CREATE TABLE user_achievement (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            achievement_id VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            points INT NOT NULL,
            unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_3F68B664A76ED395 (user_id),
            UNIQUE INDEX user_achievement_unique (user_id, achievement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key for user_achievement
        $this->addSql('ALTER TABLE user_achievement ADD CONSTRAINT FK_3F68B664A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');

        // Add owner_id column to cat table
        $this->addSql('ALTER TABLE cat ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cat ADD CONSTRAINT FK_9E5E43A87E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_9E5E43A87E3C61F9 ON cat (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key and column from cat
        $this->addSql('ALTER TABLE cat DROP FOREIGN KEY FK_9E5E43A87E3C61F9');
        $this->addSql('DROP INDEX IDX_9E5E43A87E3C61F9 ON cat');
        $this->addSql('ALTER TABLE cat DROP owner_id');

        // Remove user_achievement table
        $this->addSql('ALTER TABLE user_achievement DROP FOREIGN KEY FK_3F68B664A76ED395');
        $this->addSql('DROP TABLE user_achievement');

        // Remove user table
        $this->addSql('DROP TABLE `user`');
    }
}
