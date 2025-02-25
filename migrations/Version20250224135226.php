<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224135226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE events (id INT IDENTITY NOT NULL, external_id NVARCHAR(255) NOT NULL, source NVARCHAR(255) NOT NULL, image NVARCHAR(255) NOT NULL, title NVARCHAR(255) NOT NULL, description VARCHAR(MAX) NOT NULL, date DATETIME2(6) NOT NULL, price NUMERIC(6, 2), link NVARCHAR(255), type_event NVARCHAR(255), category VARCHAR(MAX) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:json)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'events\', N\'COLUMN\', \'category\'');
        $this->addSql('CREATE TABLE google_integration (id INT IDENTITY NOT NULL, user_id INT NOT NULL, google_id NVARCHAR(255) NOT NULL, access_token NVARCHAR(255), refresh_token NVARCHAR(255), PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D778A7A576F5C865 ON google_integration (google_id) WHERE google_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D778A7A5A76ED395 ON google_integration (user_id) WHERE user_id IS NOT NULL');
        $this->addSql('CREATE TABLE local_events (id INT IDENTITY NOT NULL, user_id INT NOT NULL, image NVARCHAR(255) NOT NULL, title NVARCHAR(255) NOT NULL, description NVARCHAR(255) NOT NULL, date DATETIME2(6) NOT NULL, price_min NUMERIC(6, 2), price_max NUMERIC(6, 2), link NVARCHAR(255), type_event NVARCHAR(255), deleted BIT, category VARCHAR(MAX) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_54F575CFA76ED395 ON local_events (user_id)');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:json)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'local_events\', N\'COLUMN\', \'category\'');
        $this->addSql('CREATE TABLE password_request_token (id INT IDENTITY NOT NULL, owner_id INT NOT NULL, token NVARCHAR(255) NOT NULL, expiration_date DATETIME2(6), created_at DATETIME2(6), PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_7561F8857E3C61F9 ON password_request_token (owner_id)');
        $this->addSql('CREATE TABLE [user] (id INT IDENTITY NOT NULL, email NVARCHAR(180) NOT NULL, roles VARCHAR(MAX) NOT NULL, password NVARCHAR(255) NOT NULL, first_name NVARCHAR(255) NOT NULL, last_name NVARCHAR(255) NOT NULL, username NVARCHAR(255), city NVARCHAR(255), user_type NVARCHAR(255) NOT NULL, terms_accepted BIT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON [user] (email) WHERE email IS NOT NULL');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:json)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'user\', N\'COLUMN\', \'roles\'');
        $this->addSql('ALTER TABLE google_integration ADD CONSTRAINT FK_D778A7A5A76ED395 FOREIGN KEY (user_id) REFERENCES [user] (id)');
        $this->addSql('ALTER TABLE local_events ADD CONSTRAINT FK_54F575CFA76ED395 FOREIGN KEY (user_id) REFERENCES [user] (id)');
        $this->addSql('ALTER TABLE password_request_token ADD CONSTRAINT FK_7561F8857E3C61F9 FOREIGN KEY (owner_id) REFERENCES [user] (id)');
        $this->addSql('DROP TABLE active_events');
        $this->addSql('DROP TABLE active_local_events');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA db_accessadmin');
        $this->addSql('CREATE SCHEMA db_backupoperator');
        $this->addSql('CREATE SCHEMA db_datareader');
        $this->addSql('CREATE SCHEMA db_datawriter');
        $this->addSql('CREATE SCHEMA db_ddladmin');
        $this->addSql('CREATE SCHEMA db_denydatareader');
        $this->addSql('CREATE SCHEMA db_denydatawriter');
        $this->addSql('CREATE SCHEMA db_owner');
        $this->addSql('CREATE SCHEMA db_securityadmin');
        $this->addSql('CREATE SCHEMA dbo');
        $this->addSql('CREATE TABLE active_events (id INT NOT NULL, image NVARCHAR(255) COLLATE Polish_CI_AS, title NVARCHAR(255) COLLATE Polish_CI_AS NOT NULL, description VARCHAR(MAX) COLLATE Polish_CI_AS, date DATETIME2(6) NOT NULL, price NUMERIC(10, 2), link NVARCHAR(255) COLLATE Polish_CI_AS, external_id INT, source NVARCHAR(255) COLLATE Polish_CI_AS, type_event NVARCHAR(255) COLLATE Polish_CI_AS, category NVARCHAR(255) COLLATE Polish_CI_AS, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE active_local_events (id INT NOT NULL, image NVARCHAR(255) COLLATE Polish_CI_AS, title NVARCHAR(255) COLLATE Polish_CI_AS NOT NULL, description VARCHAR(MAX) COLLATE Polish_CI_AS, date DATETIME2(6) NOT NULL, price_min NUMERIC(10, 2), link NVARCHAR(255) COLLATE Polish_CI_AS, user_id INT, type_event NVARCHAR(255) COLLATE Polish_CI_AS, deleted BIT, category NVARCHAR(255) COLLATE Polish_CI_AS, price_max NUMERIC(10, 2), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE google_integration DROP CONSTRAINT FK_D778A7A5A76ED395');
        $this->addSql('ALTER TABLE local_events DROP CONSTRAINT FK_54F575CFA76ED395');
        $this->addSql('ALTER TABLE password_request_token DROP CONSTRAINT FK_7561F8857E3C61F9');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE google_integration');
        $this->addSql('DROP TABLE local_events');
        $this->addSql('DROP TABLE password_request_token');
        $this->addSql('DROP TABLE [user]');
    }
}
