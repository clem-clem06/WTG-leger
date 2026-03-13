<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        // On cherche le panier de l'utilisateur
        $cart = $cartRepository->findOneBy(['user' => $user]);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    // J'ai enlevé methods: ['POST'] pour qu'on puisse juste utiliser un simple lien <a> depuis les offres
    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(Offre $offre, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // 1. Récupérer ou créer le panier
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // 2. Vérifier si l'offre est DÉJÀ dans le panier
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getOffre() === $offre) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Si elle y est, on ajoute +1 à la quantité
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            // Sinon, on crée une nouvelle ligne dans le panier
            $cartItem = new CartItem();
            $cartItem->setOffre($offre);
            $cartItem->setQuantity(1);
            $cartItem->setPrice($offre->getPrixMensuel()); // Attention : C'est en centimes !
            $cart->addCartItem($cartItem);

            $em->persist($cartItem);
        }

        $em->flush();
        $this->addFlash('success', 'L\'offre a été ajoutée à votre panier !');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(CartItem $cartItem, EntityManagerInterface $em): Response
    {
        // Sécurité : On vérifie que la ligne appartient bien au panier de l'utilisateur connecté !
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            $em->remove($cartItem);
            $em->flush();
            $this->addFlash('success', 'Offre retirée du panier.');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_cart_decrease')]
    public function decrease(CartItem $cartItem, EntityManagerInterface $em): Response
    {
        // On vérifie que le CartItem appartient bien au panier de l'utilisateur
        if ($cartItem->getCart()->getUser() === $this->getUser()) {
            if ($cartItem->getQuantity() > 1) {
                // Si > 1, on retire 1
                $cartItem->setQuantity($cartItem->getQuantity() - 1);
            } else {
                // Si = 1, on supprime la ligne complètement
                $em->remove($cartItem);
                $this->addFlash('success', 'Offre retirée du panier.');
            }
            $em->flush();
        }

        return $this->redirectToRoute('app_cart');
    }
}
