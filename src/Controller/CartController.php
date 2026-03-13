<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Offre;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        $cart = $cartRepository->findByUser($user) ?? new Cart();
        if (!$cart->getId()) {
            $cart->setUser($user);
            $cartRepository->save($cart, true);
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Offre $offre, Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findByUser($user) ?? new Cart();
        if (!$cart->getId()) {
            $cart->setUser($user);
            $em->persist($cart);
        }

        $quantity = $request->request->getInt('quantity', 1);
        $cartItem = new CartItem();
        $cartItem->setOffre($offre);
        $cartItem->setQuantity($quantity);
        $cartItem->setPrice($offre->getPrixMensuel()); // Exemple, ajustez selon besoin
        $cart->addCartItem($cartItem);

        $em->persist($cartItem);
        $em->flush();

        return $this->redirectToRoute('app_cart');
    }

    // Ajoutez remove et update similaires
}
