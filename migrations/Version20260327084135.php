<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327084135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Add validated column with default value
        $this->addSql('ALTER TABLE comment ADD validated TINYINT DEFAULT 1 NOT NULL');
        
        // Add created_at as nullable first
        $this->addSql('ALTER TABLE comment ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Set created_at to current date for existing records
        $this->addSql('UPDATE comment SET created_at = NOW() WHERE created_at IS NULL');
        
        // Make created_at non-nullable
        $this->addSql('ALTER TABLE comment MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP validated, DROP created_at');
    }
}
