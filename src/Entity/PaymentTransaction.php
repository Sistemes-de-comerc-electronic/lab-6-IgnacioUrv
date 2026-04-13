<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentTransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PaymentTransactionRepository::class)]
#[ORM\Table(
    name: 'payment_transaction',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_payment_intent_id', columns: ['payment_intent_id'])]
)]
class PaymentTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $paymentIntentId;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 10)]
    private string $currency;

    #[ORM\Column(type: 'string', length: 255)]
    private string $customerEmail;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $paymentIntentId,
        int $amount,
        string $currency,
        string $customerEmail,
    ) {
        $this->id = Uuid::v4();
        $this->paymentIntentId = $paymentIntentId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->customerEmail = $customerEmail;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPaymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
