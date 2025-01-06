<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105164713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE [user] ADD roles VARCHAR(MAX) NOT NULL');
        $this->addSql('ALTER TABLE [user] DROP COLUMN city');
        $this->addSql('ALTER TABLE [user] DROP COLUMN postal_code');
        $this->addSql('ALTER TABLE [user] DROP COLUMN user_type');
        $this->addSql('ALTER TABLE [user] DROP COLUMN terms_accepted');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:json)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'user\', N\'COLUMN\', \'roles\'');
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
        $this->addSql('ALTER TABLE [user] ADD city NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE [user] ADD postal_code NVARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE [user] ADD user_type NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE [user] ADD terms_accepted BIT NOT NULL');
        $this->addSql('ALTER TABLE [user] DROP COLUMN roles');
    }
}
