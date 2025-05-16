<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515132227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier DROP name, DROP description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier_translation ADD translatable_id INT NOT NULL, ADD locale VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier_translation ADD CONSTRAINT FK_868E66A92C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES app_supplier (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_868E66A92C2AC5D3 ON app_supplier_translation (translatable_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX app_supplier_translation_uniq_trans ON app_supplier_translation (translatable_id, locale)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier ADD name VARCHAR(255) NOT NULL, ADD description VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier_translation DROP FOREIGN KEY FK_868E66A92C2AC5D3
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_868E66A92C2AC5D3 ON app_supplier_translation
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX app_supplier_translation_uniq_trans ON app_supplier_translation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_supplier_translation DROP translatable_id, DROP locale
        SQL);
    }
}
