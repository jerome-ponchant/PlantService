<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528134828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category_implications (category_source INT NOT NULL, category_target INT NOT NULL, INDEX IDX_4E70BA505062B508 (category_source), INDEX IDX_4E70BA504987E587 (category_target), PRIMARY KEY (category_source, category_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE category_implications ADD CONSTRAINT FK_4E70BA505062B508 FOREIGN KEY (category_source) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_implications ADD CONSTRAINT FK_4E70BA504987E587 FOREIGN KEY (category_target) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_implications DROP FOREIGN KEY FK_4E70BA505062B508');
        $this->addSql('ALTER TABLE category_implications DROP FOREIGN KEY FK_4E70BA504987E587');
        $this->addSql('DROP TABLE category_implications');
    }
}
