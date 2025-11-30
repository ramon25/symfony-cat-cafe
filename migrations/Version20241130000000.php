<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241130000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_message table for storing AI chat history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            cat_id INT NOT NULL,
            session_id VARCHAR(64) NOT NULL,
            role VARCHAR(20) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_chat_message_cat (cat_id),
            INDEX idx_chat_message_session (session_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_FAB3FC16E6ADA943 FOREIGN KEY (cat_id) REFERENCES cat (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE chat_message');
    }
}
