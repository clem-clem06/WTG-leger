<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CartService
{
    public function __construct(private EntityManagerInterface $em, private CartRepository $cartRepository, private Security $security)
    {

    }

    /**
     * Petite fonction interne pour récupérer l'utilisateur facilement
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
        if ($quantiteChoisie < 1 || $dureeChoisie < 1 || $dureeChoisie > 60 || ($dureeChoisie > 9 && $dureeChoisie % 12 !== 0)) {
            throw new InvalidArgumentException('Données saisies invalides.');
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
            $prixCalcule = ($dureeChoisie >= 12 && $dureeChoisie % 12 === 0)
                ? $offre->getPrixMensuel() * 10 * ($dureeChoisie / 12)
                : $offre->getPrixMensuel() * $dureeChoisie;

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
            $cartItem->setPrice(
                ($newDuree >= 12 && $newDuree % 12 === 0)
                    ? $offre->getPrixMensuel() * 10 * ($newDuree / 12)
                    : $offre->getPrixMensuel() * $newDuree
            );
            $this->em->flush();
        }

        return $warning;
    }
}
