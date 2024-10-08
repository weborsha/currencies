<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007122835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create currency, exchange_rate, and messenger_messages tables';
    }

    public function up(Schema $schema): void
    {
        // Create tables only if they do not exist
        if (!$schema->tablesExist('currency')) {
            $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_6956883F77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        if (!$schema->tablesExist('exchange_rate')) {
            $this->addSql('CREATE TABLE exchange_rate (id INT AUTO_INCREMENT NOT NULL, currency_id INT NOT NULL, rate DOUBLE PRECISION NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E9521FAB38248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        if (!$schema->tablesExist('messenger_messages')) {
            $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        // Add foreign key constraint only if tables exist
        if ($schema->tablesExist('exchange_rate') && $schema->tablesExist('currency')) {
            $this->addSql('ALTER TABLE exchange_rate ADD CONSTRAINT FK_E9521FAB38248176 FOREIGN KEY (currency_id) REFERENCES currency (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint before dropping tables
        if ($schema->tablesExist('exchange_rate')) {
            $this->addSql('ALTER TABLE exchange_rate DROP FOREIGN KEY FK_E9521FAB38248176');
        }
        // Drop tables only if they exist
        if ($schema->tablesExist('messenger_messages')) {
            $this->addSql('DROP TABLE messenger_messages');
        }
        if ($schema->tablesExist('exchange_rate')) {
            $this->addSql('DROP TABLE exchange_rate');
        }
        if ($schema->tablesExist('currency')) {
            $this->addSql('DROP TABLE currency');
        }
    }
}
