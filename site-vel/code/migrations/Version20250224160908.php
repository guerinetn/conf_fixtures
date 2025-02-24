<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224160908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE cart_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE cart_books_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE order_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_address_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cart (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE cart_books (id INT NOT NULL, cart_id INT DEFAULT NULL, book_id INT DEFAULT NULL, quantity INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_15F1479A1AD5CDBF ON cart_books (cart_id)');
        $this->addSql('CREATE INDEX IDX_15F1479A16A2B381 ON cart_books (book_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_15F1479A1AD5CDBF16A2B381 ON cart_books (cart_id, book_id)');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, client_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F529939819EB6921 ON "order" (client_id)');
        $this->addSql('CREATE TABLE user_address (id INT NOT NULL, address_id INT DEFAULT NULL, user_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5543718BF5B7AF75 ON user_address (address_id)');
        $this->addSql('CREATE INDEX IDX_5543718BA76ED395 ON user_address (user_id)');
        $this->addSql('ALTER TABLE cart_books ADD CONSTRAINT FK_15F1479A1AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cart_books ADD CONSTRAINT FK_15F1479A16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939819EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_address ADD CONSTRAINT FK_5543718BF5B7AF75 FOREIGN KEY (address_id) REFERENCES adresse (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_address ADD CONSTRAINT FK_5543718BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD last_connected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE adresse ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE adresse ADD address1 VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE adresse ADD city VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE adresse DROP adresse1');
        $this->addSql('ALTER TABLE adresse DROP ville');
        $this->addSql('ALTER TABLE adresse RENAME COLUMN adresse2 TO adress2');
        $this->addSql('ALTER TABLE adresse RENAME COLUMN code_postal TO postal_code');
        $this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C35F0816A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C35F0816A76ED395 ON adresse (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE cart_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE cart_books_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE order_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_address_id_seq CASCADE');
        $this->addSql('ALTER TABLE cart_books DROP CONSTRAINT FK_15F1479A1AD5CDBF');
        $this->addSql('ALTER TABLE cart_books DROP CONSTRAINT FK_15F1479A16A2B381');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939819EB6921');
        $this->addSql('ALTER TABLE user_address DROP CONSTRAINT FK_5543718BF5B7AF75');
        $this->addSql('ALTER TABLE user_address DROP CONSTRAINT FK_5543718BA76ED395');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_books');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE user_address');
        $this->addSql('ALTER TABLE adresse DROP CONSTRAINT FK_C35F0816A76ED395');
        $this->addSql('DROP INDEX IDX_C35F0816A76ED395');
        $this->addSql('ALTER TABLE adresse ADD adresse1 VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE adresse ADD ville VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE adresse DROP user_id');
        $this->addSql('ALTER TABLE adresse DROP address1');
        $this->addSql('ALTER TABLE adresse DROP city');
        $this->addSql('ALTER TABLE adresse RENAME COLUMN adress2 TO adresse2');
        $this->addSql('ALTER TABLE adresse RENAME COLUMN postal_code TO code_postal');
        $this->addSql('ALTER TABLE "user" DROP last_connected_at');
    }
}
