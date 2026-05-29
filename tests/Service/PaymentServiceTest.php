<?php

namespace App\Tests\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires de la validation du paiement par carte.
 *
 * On utilise un vrai validateur Symfony (les contraintes CardScheme, Regex,
 * NotBlank et Collection sont natives, donc disponibles hors du kernel).
 */
class PaymentServiceTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidator();
    }

    public function testCarteSauvegardeeIntrouvableLeveUneException(): void
    {
        $user = new User();

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $service = new PaymentService($em, $this->createStub(ValidatorInterface::class));

        $this->expectException(\InvalidArgumentException::class);
        $service->processCard(['selectedCardId' => '999'], $user);
    }

    public function testCarteSauvegardeeValideRenvoieTokenEtLast4(): void
    {
        $user = new User();

        $card = new Card();
        $card->setUser($user);
        $card->setLast4('4242');
        $card->setExpMonth(12);
        $card->setExpYear(30);
        $card->setToken('tok_existant_123');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($card);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $service = new PaymentService($em, $this->createStub(ValidatorInterface::class));

        [$token, $last4] = $service->processCard(['selectedCardId' => '1'], $user);

        $this->assertSame('tok_existant_123', $token);
        $this->assertSame('4242', $last4);
    }

    public function testDateExpirationInvalideLeveUneException(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $service = new PaymentService($em, $this->validator());

        $this->expectException(\InvalidArgumentException::class);

        // Carte VISA de test valide (Luhn OK) mais mois d'expiration invalide (13).
        $service->processCard([
            'cardNumber' => '4111111111111111',
            'expDate' => '13/25',
        ], new User());
    }

    public function testNouvelleCarteValideSansSauvegardeRenvoieToken(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        // saveCard absent → aucune persistance attendue.
        $em->expects($this->never())->method('persist');

        $service = new PaymentService($em, $this->validator());

        [$token, $last4] = $service->processCard([
            'cardNumber' => '4111 1111 1111 1111',
            'expDate' => '12/30',
        ], new User());

        $this->assertStringStartsWith('tok_simul_', $token);
        $this->assertSame('1111', $last4);
    }
}
