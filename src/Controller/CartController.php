<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Offre $offre, Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        $dureeChoisie = (int) $request->request->get('duree', 1);
        $quantiteChoisie = (int) $request->request->get('quantite', 1);

        // ==========================================
        // 1. SÉCURITÉ PHP INFRANCHISSABLE
        // ==========================================
        if ($quantiteChoisie < 1) {
            $this->addFlash('danger', 'Quantité invalide.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offre->getId()]);
        }

        // On interdit les mois farfelus comme 23, mais on autorise 12, 24, 36...
        if ($dureeChoisie < 1 || ($dureeChoisie > 9 && $dureeChoisie % 12 !== 0)) {
            $this->addFlash('danger', 'Durée invalide. Au-delà de 9 mois, veuillez utiliser la facturation annuelle.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offre->getId()]);
        }

        // ==========================================
        // 2. RECHERCHE DE LA BONNE LIGNE (Même offre ET Même durée)
        // ==========================================
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
            $prixCalcule = $offre->getPrixMensuel() * $dureeChoisie;

            $cartItem = new CartItem();
            $cartItem->setOffre($offre);
            $cartItem->setQuantity($quantiteChoisie);
            $cartItem->setDureeMois($dureeChoisie);
            $cartItem->setPrice($prixCalcule);

            $cart->addCartItem($cartItem);
            $em->persist($cartItem);
        }

        $em->flush();
        if ($quantiteChoisie === 1) {
            $this->addFlash('success', '1 offre ajoutée pour ' . $dureeChoisie . ' mois !');
        } else {
            $this->addFlash('success', $quantiteChoisie . ' offre(s) ajoutée(s) pour ' . $dureeChoisie . ' mois !');
        }

        // Si on a cliqué sur le bouton + depuis le panier, on reste sur le panier
        if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), '/cart')) {
            return $this->redirectToRoute('app_cart');
        }

        // Sinon, on reste sur la page de l'offre
        return $this->redirectToRoute('app_offer_show', ['id' => $offre->getId()]);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, EntityManagerInterface $em): Response
    {
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            $em->remove($cartItem);
            $em->flush();
            $this->addFlash('success', 'Offre retirée du panier.');
        }
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_cart_decrease', methods: ['POST'])]
    public function decrease(CartItem $cartItem, EntityManagerInterface $em): Response
    {
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            if ($cartItem->getQuantity() > 1) {
                $cartItem->setQuantity($cartItem->getQuantity() - 1);
            } else {
                $em->remove($cartItem);
                $this->addFlash('success', 'Offre retirée du panier.');
            }
            $em->flush();
        }
        return $this->redirectToRoute('app_cart');
    }

    // ==========================================
    // 3. MODIFIER LA DURÉE DEPUIS LE PANIER
    // ==========================================
    #[Route('/cart/duree/{id}/{action}', name: 'app_cart_update_duree', methods: ['POST'])]
    public function updateDuree(CartItem $cartItem, string $action, EntityManagerInterface $em): Response
    {
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_cart');
        }

        $currentDuree = $cartItem->getDureeMois();
        $isAnnuel = ($currentDuree >= 12 && $currentDuree % 12 === 0);
        $step = $isAnnuel ? 12 : 1; // On ajoute par année ou par mois

        if ($action === 'increase') {
            $newDuree = $currentDuree + $step;
            if (!$isAnnuel && $newDuree > 9) {
                $this->addFlash('warning', 'Passez sur une offre annuelle.');
                $newDuree = 9;
            }
        } else {
            $newDuree = $currentDuree - $step;
            if ($isAnnuel && $newDuree < 12) {
                $this->addFlash('warning', 'La durée annuelle minimum est de 1 an.');
                $newDuree = 12;
            }
        }

        if ($newDuree >= 1) {
            $cartItem->setDureeMois($newDuree);
            // Recalcul du prix
            $offre = $cartItem->getOffre();
            if ($newDuree >= 12 && $newDuree % 12 === 0) {
                $cartItem->setPrice($offre->getPrixMensuel() * 10 * ($newDuree / 12));
            } else {
                $cartItem->setPrice($offre->getPrixMensuel() * $newDuree);
            }
            $em->flush();
        }

        return $this->redirectToRoute('app_cart');
    }
}
