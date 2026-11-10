<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110153100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guitars ADD created_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE guitars ADD CONSTRAINT FK_65FD8975DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_65FD8975DE12AB56 ON guitars (created_by)');
        $this->addSql('ALTER TABLE users ADD status VARCHAR(10) DEFAULT \'active\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guitars DROP FOREIGN KEY FK_65FD8975DE12AB56');
        $this->addSql('DROP INDEX IDX_65FD8975DE12AB56 ON guitars');
        $this->addSql('ALTER TABLE guitars DROP created_by');
        $this->addSql('ALTER TABLE users DROP status');
    }
}
