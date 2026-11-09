<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109014134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart_items (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, added_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, guitar_id INT NOT NULL, UNIQUE INDEX uniq_cart_user_guitar (user_id, guitar_id), INDEX IDX_BEF48445A76ED395 (user_id), INDEX IDX_BEF4844548420B1E (guitar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guitars (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, brand VARCHAR(80) NOT NULL, category VARCHAR(60) NOT NULL, price NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT \'\' NOT NULL, description LONGTEXT DEFAULT NULL, is_bestseller TINYINT DEFAULT 0 NOT NULL, is_new TINYINT DEFAULT 0 NOT NULL, item_code VARCHAR(20) NOT NULL, availability VARCHAR(20) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_65FD8975BF257463 (item_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, order_id INT NOT NULL, guitar_id INT NOT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), INDEX IDX_62809DB048420B1E (guitar_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, total NUMERIC(10, 2) NOT NULL, fulfillment VARCHAR(20) DEFAULT \'delivery\' NOT NULL, payment_method VARCHAR(20) NOT NULL, payment_ref VARCHAR(40) DEFAULT NULL, payment_verified_at DATETIME DEFAULT NULL, fullname VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(120) NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, status_updated_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, payment_verified_by INT DEFAULT NULL, INDEX idx_orders_status (status), INDEX idx_orders_created_at (created_at), INDEX IDX_E52FFDEEA76ED395 (user_id), INDEX IDX_E52FFDEE965A7065 (payment_verified_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_3967A216B3BC57DA (token_hash), INDEX IDX_3967A216A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(40) DEFAULT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(10) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF48445A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF4844548420B1E FOREIGN KEY (guitar_id) REFERENCES guitars (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB048420B1E FOREIGN KEY (guitar_id) REFERENCES guitars (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE965A7065 FOREIGN KEY (payment_verified_by) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF48445A76ED395');
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF4844548420B1E');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB048420B1E');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE965A7065');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('DROP TABLE cart_items');
        $this->addSql('DROP TABLE guitars');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
