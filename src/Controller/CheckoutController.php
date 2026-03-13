<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
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
        // ... (Ton code actuel pour afficher le checkout reste identique) ...
        $cart = $cartRepository->findByUser($this->getUser());
        if (!$cart || $cart->cartItems->isEmpty()) {
            return $this->redirectToRoute('app_cart');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $cart = $cartRepository->findByUser($this->getUser());
        if (!$cart) {
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();

        // 1. GESTION DE LA CARTE BANCAIRE (Simulation PCI-DSS)
        $cardNumber = $request->request->get('cardNumber');
        $expDate = $request->request->get('expDate'); // Format MM/YY
        $saveCard = $request->request->get('saveCard'); // Vaut "1" si coché

        // Si l'utilisateur a demandé à sauvegarder la carte
        if ($saveCard === "1" && $cardNumber && $expDate) {
            // On nettoie les espaces du numéro de carte
            $cleanCardNumber = str_replace(' ', '', $cardNumber);
            // On extrait les 4 derniers chiffres
            $last4 = substr($cleanCardNumber, -4);

            // On sépare le mois et l'année (ex: 12/28 -> mois: 12, année: 28)
            $dateParts = explode('/', $expDate);
            $expMonth = isset($dateParts[0]) ? (int)$dateParts[0] : 12;
            $expYear = isset($dateParts[1]) ? (int)$dateParts[1] : 99;

            // Simulation : Création d'un token aléatoire (comme le ferait Stripe)
            $fakeBankToken = 'tok_simul_' . bin2hex(random_bytes(8));

            $card = new Card();
            $card->setUser($user);
            $card->setLast4($last4);
            $card->setExpMonth($expMonth);
            $card->setExpYear($expYear);
            $card->setToken($fakeBankToken);

            $em->persist($card);
        }

        // 2. CRÉATION DE LA COMMANDE ET DU PAIEMENT (Ton code existant)
        $total = 0;
        foreach ($cart->cartItems as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        $order = new Order();
        $order->setUser($user);
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

        // On peut stocker le token dans le gatewayResponse pour garder une trace
        $tokenTrace = isset($fakeBankToken) ? ' avec la carte finissant par ' . $last4 : '';
        $payment->setGatewayResponse('Paiement simulé réussi' . $tokenTrace);

        $em->persist($order);
        $em->persist($payment);

        // 3. VIDER LE PANIER
        foreach ($cart->cartItems as $item) {
            $em->remove($item);
        }

        $em->flush();

        $this->addFlash('success', 'Paiement simulé réussi !');

        return $this->redirectToRoute('app_offer');
    }
}
