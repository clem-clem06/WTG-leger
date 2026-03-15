<?php
namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use Doctrine\ORM\EntityManagerInterface;

readonly class CartService
{
    public function __construct(private EntityManagerInterface $em) {

    }

    public function addToCart(Cart $cart, Offre $offre, int $quantiteChoisie, int $dureeChoisie): void
    {
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getOffre() === $offre && $item->getDureeMois() === $dureeChoisie) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + $quantiteChoisie);
        } else {
            if ($dureeChoisie >= 12 && $dureeChoisie % 12 === 0) {
                $prixCalcule = $offre->getPrixMensuel() * 10 * ($dureeChoisie / 12);
            } else {
                $prixCalcule = $offre->getPrixMensuel() * $dureeChoisie;
            }

            $cartItem = new CartItem();
            $cartItem->setOffre($offre);
            $cartItem->setQuantity($quantiteChoisie);
            $cartItem->setDureeMois($dureeChoisie);
            $cartItem->setPrice($prixCalcule);

            $cart->addCartItem($cartItem);
            $this->em->persist($cartItem);
        }

        $this->em->flush();
    }

    public function updateDuree(CartItem $cartItem, string $action): ?string
    {
        $currentDuree = $cartItem->getDureeMois();
        $isAnnuel = ($currentDuree >= 12 && $currentDuree % 12 === 0);
        $step = $isAnnuel ? 12 : 1;
        $warning = null;

        if ($action === 'increase') {
            $newDuree = $currentDuree + $step;
            if (!$isAnnuel && $newDuree > 9) {
                $warning = 'Passez sur une offre annuelle.';
                $newDuree = 9;
            } elseif ($isAnnuel && $newDuree > 60) {
                $warning = 'La durée maximale d\'engagement est de 5 ans.';
                $newDuree = 60;
            }
        } else {
            $newDuree = $currentDuree - $step;
            if ($isAnnuel && $newDuree < 12) {
                $warning = 'La durée annuelle minimum est de 1 an.';
                $newDuree = 12;
            }
        }

        if ($newDuree >= 1) {
            $cartItem->setDureeMois($newDuree);
            $offre = $cartItem->getOffre();
            if ($newDuree >= 12 && $newDuree % 12 === 0) {
                $cartItem->setPrice($offre->getPrixMensuel() * 10 * ($newDuree / 12));
            } else {
                $cartItem->setPrice($offre->getPrixMensuel() * $newDuree);
            }
            $this->em->flush();
        }

        return $warning; // Renvoie l'avertissement
    }
}
