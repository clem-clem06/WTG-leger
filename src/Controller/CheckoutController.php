<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Repository\CartRepository;
use App\Repository\UniteRepository;
use DateMalformedStringException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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

        $savedCards = $em->getRepository(Card::class)->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'savedCards' => $savedCards,
        ]);
    }

    /**
     * @throws RandomException
     * @throws DateMalformedStringException
     */
    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, CartRepository $cartRepository, UniteRepository $uniteRepository, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return $this->redirectToRoute('app_cart');
        }

        $last4 = null;
        $fakeBankToken = null;

        $selectedCardId = $request->request->get('selectedCardId');

        if ($selectedCardId) {
            $card = $em->getRepository(Card::class)->findOneBy(['id' => $selectedCardId, 'user' => $user]);
            if ($card) {
                $last4 = $card->getLast4();
                $fakeBankToken = $card->getToken();
            }
        } else {
            // On force en string pour éviter les erreurs si c'est null
            $cardNumber = (string)$request->request->get('cardNumber', '');
            $expDate = (string)$request->request->get('expDate', '');
            $saveCard = $request->request->get('saveCard');

            $cleanCardNumber = str_replace(' ', '', $cardNumber);
            $cleanExpDate = str_replace(' ', '', $expDate);

            // ==========================================
            // 1. VALIDATION STRICTE
            // ==========================================
            $constraints = new Assert\Collection([
                'cardNumber' => [
                    new Assert\NotBlank(message: 'Le numéro de carte est obligatoire.'),
                    new Assert\Length(min: 14, max: 19, minMessage: 'Numéro de carte trop court.')
                ],
                'expDate' => new Assert\Regex(
                    pattern: '/^(0[1-9]|1[0-2])\/?([0-9]{2})$/',
                    message: 'Format de date d\'expiration invalide (MM/AA ou MMAA).'
                )
            ]);

            $violations = $validator->validate([
                'cardNumber' => $cleanCardNumber,
                'expDate' => $cleanExpDate
            ], $constraints);

            // Si le vigile trouve une anomalie, on le renvoie à l'accueil avec une gifle (flash)
            if (count($violations) > 0) {
                // On affiche le premier message d'erreur trouvé
                $this->addFlash('danger', $violations[0]->getMessage());
                return $this->redirectToRoute('app_checkout');
            }

            // ==========================================
            // 2. TRAITEMENT SÉCURISÉ
            // ==========================================
            $last4 = substr($cleanCardNumber, -4);

            // Grâce au Validator Regex juste au-dessus, on est 100% certains du format.
            // Ça ne peut plus jamais exploser ici !
            if (str_contains($cleanExpDate, '/')) {
                $dateParts = explode('/', $cleanExpDate);
                $expMonth = (int)$dateParts[0];
                $expYear = (int)$dateParts[1];
            } else {
                $expMonth = (int)substr($cleanExpDate, 0, 2);
                $expYear = (int)substr($cleanExpDate, 2, 2);
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

        // ==========================================
        // 1. CRÉATION COMMANDE
        // ==========================================
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
            $orderItem->setDureeMois($item->getDureeMois());
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

        // ==========================================
        // 2. ATTRIBUTION DES UNITÉS
        // ==========================================

        // On démarre explicitement une transaction SQL
        $em->beginTransaction();

        try {
            foreach ($cart->getCartItems() as $item) {
                $unitesRequises = $item->getOffre()->getNombreUnites() * $item->getQuantity();
                $dureeMois = $item->getDureeMois();
                $dateFin = new DateTime('+' . $dureeMois . ' months');

                $unitesDisponibles = $uniteRepository->findAndLockAvailableUnites($unitesRequises);

                // S'il n'y a plus assez de stock, on lève une exception pour annuler la transaction !
                if (count($unitesDisponibles) < $unitesRequises) {
                    $em->rollback();

                    $this->addFlash('danger', 'Désolé, un autre client vient de réserver les dernières unités de l\'offre ' . $item->getOffre()->getNom());

                    return $this->redirectToRoute('app_cart');
                }

                foreach ($unitesDisponibles as $unite) {
                    $unite->setLocataire($user);
                    $unite->setDateFinLocation($dateFin);
                    $em->persist($unite);
                }

                $em->flush();
            }

            // ==========================================
            // 3. VIDER LE PANIER
            // ==========================================
            foreach ($cart->getCartItems() as $item) {
                $em->remove($item);
            }
            $em->flush();

            $em->commit();

            $this->addFlash('success', 'Paiement simulé réussi ! Vos unités ont été attribuées et sécurisées.');
            return $this->redirectToRoute('app_home'); //TODO: On pourra rediriger vers l'espace client plus tard !

        } catch (Exception) {
            // SI QUELQUE CHOSE PLANTE (Rupture de stock, etc.)
            // On annule ABSOLUMENT TOUT (y compris la commande et le paiement qu'on avait persistés plus haut)
            $em->rollback();

            $this->addFlash('danger', 'Une erreur technique inattendue est survenue.');
            return $this->redirectToRoute('app_cart');
        }
    }
}
