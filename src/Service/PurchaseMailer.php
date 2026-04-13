<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class PurchaseMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function sendThankYouEmail(string $to): void
    {
        $email = (new Email())
            ->from(new Address('no-reply@botiga.local', 'Botiga Online'))
            ->to(new Address($to))
            ->subject('Gràcies per la teva compra')
            ->text("Gràcies per la teva compra!\n\nHem rebut el teu pagament de 20,00 € correctament.");

        $this->mailer->send($email);
    }
}
