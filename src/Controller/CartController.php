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
    public function add(Offre $offre, Request $request, CartRepository $cartRepository, EntityManagerInterface $em, $cartService): Response
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

        if ($quantiteChoisie < 1 || $dureeChoisie < 1 || $dureeChoisie > 60 || ($dureeChoisie > 9 && $dureeChoisie % 12 !== 0)) {
            $this->addFlash('danger', 'Données saisies invalides.');
            return $this->redirectToRoute('app_offer_show', ['id' => $offre->getId()]);
        }

        $cartService->addToCart($cart, $offre, $quantiteChoisie, $dureeChoisie);

        $this->addFlash('success', "$quantiteChoisie offre(s) ajoutée(s) pour $dureeChoisie mois !");

        if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), '/cart')) {
            return $this->redirectToRoute('app_cart');
        }
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
    public function updateDuree(CartItem $cartItem, string $action, $cartService): Response
    {
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_cart');
        }

        // === APPEL AU SERVICE ===
        $warningMessage = $cartService->updateDuree($cartItem, $action);

        if ($warningMessage) {
            $this->addFlash('warning', $warningMessage);
        }

        return $this->redirectToRoute('app_cart');
        }
}
