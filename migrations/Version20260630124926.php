<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630124926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_order_item ADD units_data JSON NOT NULL
        SQL);

        $this->addSql('ALTER TABLE sylius_adjustment
                           DROP FOREIGN KEY FK_ACA6E0F2F720C233');

        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_order_item_unit CHANGE id id INT NOT NULL
        SQL);

        $this->addSql('ALTER TABLE sylius_adjustment
                           ADD CONSTRAINT FK_ACA6E0F2F720C233
                           FOREIGN KEY (order_item_unit_id)
                           REFERENCES sylius_order_item_unit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_order_item DROP units_data
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sylius_order_item_unit CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
    }
}
