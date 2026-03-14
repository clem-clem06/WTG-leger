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
Final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->redirectToRoute('app_cart');
        }

        // findBy pour récupérer TOUTES les cartes de l'utilisateur
        $savedCards = $em->getRepository(Card::class)->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'savedCards' => $savedCards, // On envoie le tableau complet à la vue
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return $this->redirectToRoute('app_cart');
        }

        $last4 = null;
        $fakeBankToken = null;

        // On récupère l'ID de la carte sélectionnée
        $selectedCardId = $request->request->get('selectedCardId');

        if ($selectedCardId) {
            // Le client veut payer avec l'une de ses cartes enregistrées
            // Sécurité : on s'assure que la carte appartient bien à l'utilisateur connecté !
            $card = $em->getRepository(Card::class)->findOneBy(['id' => $selectedCardId, 'user' => $user]);
            if ($card) {
                $last4 = $card->getLast4();
                $fakeBankToken = $card->getToken();
            }
        } else {
            // Le client utilise une NOUVELLE carte
            $cardNumber = $request->request->get('cardNumber');
            $expDate = $request->request->get('expDate');
            $saveCard = $request->request->get('saveCard');

            if ($cardNumber && $expDate) {
                $cleanCardNumber = str_replace(' ', '', $cardNumber);
                $last4 = substr($cleanCardNumber, -4);

                $cleanExpDate = str_replace(' ', '', $expDate);
                if (str_contains($cleanExpDate, '/')) {
                    $dateParts = explode('/', $cleanExpDate);
                    $expMonth = (int) $dateParts[0];
                    $expYear = (int) $dateParts[1];
                } else {
                    $expMonth = (int) substr($cleanExpDate, 0, 2);
                    $expYear = (int) substr($cleanExpDate, 2, 2);
                }

                $fakeBankToken = 'tok_simul_' . bin2hex(random_bytes(8));

                if ($saveCard === "1") {
                    $card = new Card();
                    $card->setUser($user);
                    $card->setLast4($last4);
                    $card->setExpMonth($expMonth);
                    $card->setExpYear($expYear);
                    $card->setToken($fakeBankToken);
                    $em->persist($card);
                }
            }
        }

        // CRÉATION DE LA COMMANDE
        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        $order = new Order();
        $order->setUser($user);
        $order->setTotal($total);
        $order->setStatus('pending');

        foreach ($cart->getCartItems() as $item) {
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
        $payment->setStatus('completed');

        $tokenTrace = $fakeBankToken ? ' avec la carte finissant par ' . $last4 : '';
        $payment->setGatewayResponse('Paiement simulé réussi' . $tokenTrace);

        $em->persist($order);
        $em->persist($payment);

        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }
        $em->flush();

        $this->addFlash('success', 'Paiement simulé réussi !');
        return $this->redirectToRoute('app_home');
    }
}
