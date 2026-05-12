<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512065736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motw ADD art_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE motw ADD CONSTRAINT FK_2EB124B271088DEF FOREIGN KEY (art_type_id) REFERENCES art_type (id)');
        $this->addSql('CREATE INDEX IDX_2EB124B271088DEF ON motw (art_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motw DROP FOREIGN KEY FK_2EB124B271088DEF');
        $this->addSql('DROP INDEX IDX_2EB124B271088DEF ON motw');
        $this->addSql('ALTER TABLE motw DROP art_type_id');
    }
}
