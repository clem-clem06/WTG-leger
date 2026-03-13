<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findByUser($this->getUser());
        if (!$cart || $cart->cartItems->isEmpty()) {
            return $this->redirectToRoute('app_cart');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $cart = $cartRepository->findByUser($this->getUser());
        if (!$cart) {
            return $this->redirectToRoute('app_cart');
        }

        // Simuler le paiement
        $total = 0;
        foreach ($cart->cartItems as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        $order = new Order();
        $order->setUser($this->getUser());
        $order->setTotal($total);
        $order->setStatus('pending');

        foreach ($cart->cartItems as $item) {
            $orderItem = new OrderItem();
            $orderItem->setOffre($item->getOffre());
            $orderItem->setQuantity($item->getQuantity());
            $orderItem->setPrice($item->getPrice());
            $order->addOrderItem($orderItem);
            $em->persist($orderItem);
        }

        $payment = new Payment();
        $payment->setOrderRef($order);
        $payment->setAmount($total);
        $payment->setStatus('completed'); // Simulé
        $payment->setGatewayResponse('Paiement simulé réussi');

        $em->persist($order);
        $em->persist($payment);
        $em->flush();

        // Vider le panier
        foreach ($cart->cartItems as $item) {
            $em->remove($item);
        }
        $em->flush();

        $this->addFlash('success', 'Paiement simulé réussi !');

        return $this->redirectToRoute('app_offer');
    }
}
