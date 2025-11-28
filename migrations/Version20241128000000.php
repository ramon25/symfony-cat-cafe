<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241128000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cat table for the cat cafe simulation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cat (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            breed VARCHAR(50) NOT NULL,
            age INT NOT NULL,
            color VARCHAR(50) NOT NULL,
            mood VARCHAR(20) NOT NULL,
            hunger INT NOT NULL,
            happiness INT NOT NULL,
            energy INT NOT NULL,
            adopted TINYINT(1) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            adopted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cat');
    }
}
