<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Offre;
use App\Form\AddToCartType;
use App\Service\CartService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getOrCreateCart(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Offre $offre, Request $request, CartService $cartService): Response
    {
        $form = $this->createForm(AddToCartType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $duree = $data['duree'];
            $quantite = $data['quantite'];

            try {
                $cartService->addToCart($offre, $quantite, $duree);
                if ($quantite > 1) {
                    $this->addFlash('success', "$quantite offre) ajoutées pour $duree mois !");
                } else {
                    $this->addFlash('success', "$quantite offres ajoutées pour $duree mois !");
                }
            } catch (InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Données saisies invalides.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_offer_show', ['id' => $offre->getId()]));
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, CartService $cartService): Response
    {
        $cartService->remove($cartItem);
        $this->addFlash('success', 'Offre retirée du panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_cart_decrease', methods: ['POST'])]
    public function decrease(CartItem $cartItem, CartService $cartService): Response
    {
        $cartService->decrease($cartItem);
        $this->addFlash('success', 'Quantité diminuée.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/increase/{id}', name: 'app_cart_increase', methods: ['POST'])]
    public function increase(CartItem $cartItem, CartService $cartService): Response
    {
        $cartService->increase($cartItem);
        $this->addFlash('success', 'Quantité augmentée.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/duree/{id}/{action}', name: 'app_cart_update_duree', methods: ['POST'])]
    public function updateDuree(CartItem $cartItem, string $action, CartService $cartService): Response
    {
        if ($warning = $cartService->updateDuree($cartItem, $action)) {
            $this->addFlash('warning', $warning);
        }

        return $this->redirectToRoute('app_cart');
    }
}
