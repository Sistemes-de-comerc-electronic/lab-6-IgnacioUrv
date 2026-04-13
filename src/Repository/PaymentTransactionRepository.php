<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PaymentTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    public function findOneByPaymentIntentId(string $paymentIntentId): ?PaymentTransaction
    {
        return $this->findOneBy(['paymentIntentId' => $paymentIntentId]);
    }

    public function save(PaymentTransaction $paymentTransaction, bool $flush = false): void
    {
        $this->getEntityManager()->persist($paymentTransaction);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
