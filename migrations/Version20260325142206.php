<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325142206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_order ADD CONSTRAINT FK_23FA1E55A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25274CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE intervention_unite ADD CONSTRAINT FK_6801DD9C8EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention_unite ADD CONSTRAINT FK_6801DD9CEC4A74AB FOREIGN KEY (unite_id) REFERENCES unite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09E238517C FOREIGN KEY (order_ref_id) REFERENCES app_order (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE payment DROP pay_at');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DE238517C FOREIGN KEY (order_ref_id) REFERENCES app_order (id)');
        $this->addSql('ALTER TABLE unite ADD CONSTRAINT FK_1D64C11843375062 FOREIGN KEY (baie_id) REFERENCES baie (id)');
        $this->addSql('ALTER TABLE unite ADD CONSTRAINT FK_1D64C118D8A38199 FOREIGN KEY (locataire_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_order DROP FOREIGN KEY FK_23FA1E55A76ED395');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3A76ED395');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25274CC8505A');
        $this->addSql('ALTER TABLE intervention_unite DROP FOREIGN KEY FK_6801DD9C8EAE3863');
        $this->addSql('ALTER TABLE intervention_unite DROP FOREIGN KEY FK_6801DD9CEC4A74AB');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09E238517C');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094CC8505A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DE238517C');
        $this->addSql('ALTER TABLE payment ADD pay_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE unite DROP FOREIGN KEY FK_1D64C11843375062');
        $this->addSql('ALTER TABLE unite DROP FOREIGN KEY FK_1D64C118D8A38199');
    }
}
