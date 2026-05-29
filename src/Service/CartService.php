<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CartService
{
    public function __construct(private EntityManagerInterface $em, private CartRepository $cartRepository, private Security $security)
    {
    }

    /**
     * Petite fonction interne pour récupérer l'utilisateur facilement.
     */
    private function getUser(): User
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $user;
    }

    public function getOrCreateCart(): Cart
    {
        $user = $this->getUser();

        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    public function addToCart(Offre $offre, int $quantiteChoisie, int $dureeChoisie): void
    {
        if ($quantiteChoisie < 1 || $dureeChoisie < 1 || $dureeChoisie > 60 || ($dureeChoisie > 9 && 0 !== $dureeChoisie % 12)) {
            throw new \InvalidArgumentException('Données saisies invalides.');
        }

        $cart = $this->getOrCreateCart();

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
            $cartItem = new CartItem();
            $cartItem->setOffre($offre);
            $cartItem->setQuantity($quantiteChoisie);
            $cartItem->setDureeMois($dureeChoisie);
            $cartItem->setPrice($this->computePrice($offre->getPrixMensuel(), $dureeChoisie));

            $cart->addCartItem($cartItem);
            $this->em->persist($cartItem);
        }

        $this->em->flush();
    }

    /**
     * Calcule le prix d'une ligne (snapshot) à partir du tarif mensuel courant
     * et de la durée choisie. À partir d'1 an, on applique la remise : 10 mois facturés / an.
     */
    public function computePrice(int $prixMensuel, int $dureeMois): int
    {
        return ($dureeMois >= 12 && 0 === $dureeMois % 12)
            ? $prixMensuel * 10 * ($dureeMois / 12)
            : $prixMensuel * $dureeMois;
    }

    /**
     * Recalcule le prix de chaque ligne depuis le tarif courant de l'Offre
     * et met à jour le panier si un tarif a évolué depuis l'ajout.
     *
     * @return bool true si au moins une ligne a été réajustée
     */
    public function refreshPricesIfOutdated(Cart $cart): bool
    {
        $hasChanged = false;

        foreach ($cart->getCartItems() as $item) {
            $prixActuel = $this->computePrice(
                $item->getOffre()->getPrixMensuel(),
                $item->getDureeMois()
            );

            if ($item->getPrice() !== $prixActuel) {
                $item->setPrice($prixActuel);
                $hasChanged = true;
            }
        }

        if ($hasChanged) {
            $this->em->flush();
        }

        return $hasChanged;
    }

    public function remove(CartItem $cartItem): void
    {
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            $this->em->remove($cartItem);
            $this->em->flush();
        }
    }

    public function decrease(CartItem $cartItem): void
    {
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            if ($cartItem->getQuantity() > 1) {
                $cartItem->setQuantity($cartItem->getQuantity() - 1);
            } else {
                $this->em->remove($cartItem);
            }
            $this->em->flush();
        }
    }

    public function increase(CartItem $cartItem): void
    {
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
            $this->em->flush();
        }
    }

    public function updateDuree(CartItem $cartItem, string $action): ?string
    {
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return null;
        }

        $currentDuree = $cartItem->getDureeMois();
        $isAnnuel = ($currentDuree >= 12 && 0 === $currentDuree % 12);
        $step = $isAnnuel ? 12 : 1;
        $warning = null;

        if ('increase' === $action) {
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
            $cartItem->setPrice(
                $this->computePrice($cartItem->getOffre()->getPrixMensuel(), $newDuree)
            );
            $this->em->flush();
        }

        return $warning;
    }
}
