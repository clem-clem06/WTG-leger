<?php

namespace App\Tests\Service;

use App\Repository\CartRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Tests unitaires purs de la logique tarifaire du panier.
 *
 * computePrice() ne dépend d'aucun service : les dépendances du constructeur
 * sont mockées uniquement pour pouvoir instancier CartService.
 */
class CartServiceTest extends TestCase
{
    private function makeService(): CartService
    {
        // computePrice() n'utilise aucune dépendance : de simples stubs suffisent.
        return new CartService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CartRepository::class),
            $this->createStub(Security::class),
        );
    }

    #[DataProvider('fournisseurDePrix')]
    public function testComputePrice(int $prixMensuel, int $dureeMois, int $attendu): void
    {
        $prix = $this->makeService()->computePrice($prixMensuel, $dureeMois);

        $this->assertSame($attendu, $prix);
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function fournisseurDePrix(): array
    {
        // prixMensuel exprimé en centimes (10000 ¢ = 100 €)
        return [
            '1 mois (mensuel)' => [10000, 1, 10000],
            '9 mois (mensuel)' => [10000, 9, 90000],
            '11 mois non multiple de 12 (mensuel)' => [10000, 11, 110000],
            '12 mois = 1 an, 10 mois facturés' => [10000, 12, 100000],
            '24 mois = 2 ans' => [10000, 24, 200000],
            '36 mois = 3 ans' => [10000, 36, 300000],
            '60 mois = 5 ans' => [10000, 60, 500000],
            'autre tarif annuel (PME 1680€/mois, 1 an)' => [168000, 12, 1680000],
        ];
    }

    public function testComputePriceRenvoieUnEntier(): void
    {
        // La remise annuelle multiplie par un float (dureeMois / 12) : on garantit
        // que le résultat reste un int exact (pas de centime fractionnaire).
        $prix = $this->makeService()->computePrice(10000, 24);

        $this->assertIsInt($prix);
        $this->assertSame(200000, $prix);
    }

    public function testRemiseAnnuelleEstMoinsChereQueLeMensuelEquivalent(): void
    {
        $service = $this->makeService();

        $annuel = $service->computePrice(10000, 12);     // 10 mois facturés
        $mensuelEquivalent = 10000 * 12;                 // 12 mois sans remise

        $this->assertLessThan($mensuelEquivalent, $annuel);
        $this->assertSame(20000, $mensuelEquivalent - $annuel); // 2 mois offerts
    }
}
