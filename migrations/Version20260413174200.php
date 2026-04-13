<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413174200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payment_transaction table for Stripe webhook idempotency and traceability';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payment_transaction (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', payment_intent_id VARCHAR(255) NOT NULL, amount INT NOT NULL, currency VARCHAR(10) NOT NULL, customer_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_payment_intent_id (payment_intent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment_transaction');
    }
}
