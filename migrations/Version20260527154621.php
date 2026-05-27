<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527154621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment_like (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8A55E25FF8697D13 (comment_id), INDEX IDX_8A55E25FA76ED395 (user_id), UNIQUE INDEX unique_user_comment_like (user_id, comment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A55E25FF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A55E25FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A55E25FF8697D13');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A55E25FA76ED395');
        $this->addSql('DROP TABLE comment_like');
    }
}
