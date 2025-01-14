<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250114191332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE google_integration (id INT IDENTITY NOT NULL, user_id INT NOT NULL, google_id NVARCHAR(255) NOT NULL, access_token NVARCHAR(255), refresh_token NVARCHAR(255), PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D778A7A576F5C865 ON google_integration (google_id) WHERE google_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D778A7A5A76ED395 ON google_integration (user_id) WHERE user_id IS NOT NULL');
        $this->addSql('ALTER TABLE google_integration ADD CONSTRAINT FK_D778A7A5A76ED395 FOREIGN KEY (user_id) REFERENCES [user] (id)');
        $this->addSql('ALTER TABLE events ALTER COLUMN type_event NVARCHAR(255)');
        $this->addSql('ALTER TABLE local_events ALTER COLUMN type_event NVARCHAR(255)');
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
        $this->addSql('ALTER TABLE google_integration DROP CONSTRAINT FK_D778A7A5A76ED395');
        $this->addSql('DROP TABLE google_integration');
        $this->addSql('ALTER TABLE events ALTER COLUMN type_event NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE local_events ALTER COLUMN type_event NVARCHAR(255) NOT NULL');
    }
}
