<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class PaymentService
{
    public function __construct(private EntityManagerInterface $em, private ValidatorInterface $validator) {

    }

    /**
     * Traite la carte bancaire, la sauvegarde si demandé, et renvoie le Token et les 4 derniers chiffres.
     * Renvoie un tableau : [string $fakeBankToken, string $last4]
     * @throws RandomException
     */
    public function processCard(array $data, User $user): array
    {
        $selectedCardId = $data['selectedCardId']?? '';

        // 1. Si l'utilisateur a choisi une carte sauvegardée
        if ($selectedCardId) {
            $card = $this->em->getRepository(Card::class)->findOneBy(['id' => $selectedCardId, 'user' => $user]);
            if ($card) {
                return [$card->getToken(), $card->getLast4()];
            }
        }

        // 2. Si c'est une nouvelle carte saisie à la main
        $cardNumber = $data['cardNumber'] ?? '';
        $expDate = $data['expDate'] ?? '';

        $cleanCardNumber = str_replace(' ', '', $cardNumber);
        $cleanExpDate = str_replace(' ', '', $expDate);

        // Validation stricte
        $constraints = new Collection([
            'cardNumber' => [
                new NotBlank(message: 'Le numéro de carte est obligatoire.'),
                new Length(min: 14, max: 19, minMessage: 'Numéro de carte trop court.')
            ],
            'expDate' => new Regex(
                pattern: '/^(0[1-9]|1[0-2])\/?([0-9]{2})$/',
                message: 'Format de date d\'expiration invalide (MM/AA ou MMAA).'
            )
        ]);

        $violations = $this->validator->validate([
            'cardNumber' => $cleanCardNumber,
            'expDate' => $cleanExpDate
        ], $constraints);

        if (count($violations) > 0) {
            // On renvoie l'erreur au contrôleur
            throw new InvalidArgumentException($violations[0]->getMessage());
        }

        $last4 = substr($cleanCardNumber, -4);
        $fakeBankToken = 'tok_simul_' . bin2hex(random_bytes(16));
        //TODO: mettre de la sécu

        // 3. Sauvegarde de la carte si la case est cochée
        $saveCard = $data['saveCard'] ?? false;

        if ($saveCard) {
            if (str_contains($cleanExpDate, '/')) {
                $dateParts = explode('/', $cleanExpDate);
                $expMonth = (int)$dateParts[0];
                $expYear = (int)$dateParts[1];
            } else {
                $expMonth = (int)substr($cleanExpDate, 0, 2);
                $expYear = (int)substr($cleanExpDate, 2, 2);
            }

            $card = new Card();
            $card->setUser($user);
            $card->setLast4($last4);
            $card->setExpMonth($expMonth);
            $card->setExpYear($expYear);
            $card->setToken($fakeBankToken);

            $this->em->persist($card);
            $this->em->flush();
        }

        return [$fakeBankToken, $last4];
    }
}
