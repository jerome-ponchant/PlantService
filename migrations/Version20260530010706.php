<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530010706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Création de la table image (généré automatiquement par make:migration)
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, plant_id INT NOT NULL, url VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, INDEX IDX_C53D045F1D935652 (plant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F1D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) ON DELETE CASCADE');

        // 2. SCRIPT DE MIGRATION DES DONNÉES (À AJOUTER À LA MAIN)
        // On prend toutes les imageUrl non nulles de la table plant et on les insère dans image
        $this->addSql('INSERT INTO image (plant_id, url, position) SELECT id, image_url, 0 FROM plant WHERE image_url IS NOT NULL');

        // 3. Suppression de l'ancienne colonne devenue inutile
        $this->addSql('ALTER TABLE plant DROP image_url');
    }

    public function down(Schema $schema): void
    {
        // En cas de retour en arrière (down), on recrée la colonne et on remet la première image
        $this->addSql('ALTER TABLE plant ADD image_url VARCHAR(255) DEFAULT NULL');
        // On reprend l'image à la position 0 pour la remettre dans plant
        $this->addSql('UPDATE plant p SET p.image_url = (SELECT i.url FROM image i WHERE i.plant_id = p.id ORDER BY i.position ASC LIMIT 1)');

        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F1D935652');
        $this->addSql('DROP TABLE image');
    }
}
