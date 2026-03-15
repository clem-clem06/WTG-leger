<?php
namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\UniteRepository;
use DateMalformedStringException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

readonly class CheckoutService
{
    public function __construct(private EntityManagerInterface $em, private UniteRepository $uniteRepository
    ) {

    }

    /**
     * Traite la commande de A à Z (Coffre-fort SQL)
     * @throws DateMalformedStringException
     */
    public function processCheckout(User $user, Cart $cart, ?string $fakeBankToken, ?string $last4): void
    {
        $this->em->beginTransaction();

        try {
            // 1. CRÉATION COMMANDE ET PAIEMENT
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
                $this->em->persist($orderItem);
            }

            $payment = new Payment();
            $payment->setOrderRef($order);
            $payment->setAmount($total);
            $payment->setStatus('completed');
            $tokenTrace = $fakeBankToken ? ' avec la carte finissant par ' . $last4 : '';
            $payment->setGatewayResponse('Paiement simulé réussi' . $tokenTrace);

            $this->em->persist($order);
            $this->em->persist($payment);

            // 2. ATTRIBUTION (AVEC VERROU PESSIMISTE)
            foreach ($cart->getCartItems() as $item) {
                $unitesRequises = $item->getOffre()->getNombreUnites() * $item->getQuantity();
                $dateFin = new DateTime('+' . $item->getDureeMois() . ' months');

                $unitesDisponibles = $this->uniteRepository->findAndLockAvailableUnites($unitesRequises);

                if (count($unitesDisponibles) < $unitesRequises) {
                    // On déclenche une erreur qui sera attrapée par le contrôleur !
                    throw new RuntimeException('Désolé, un autre client vient de réserver les dernières unités de l\'offre ' . $item->getOffre()->getNom());
                }

                foreach ($unitesDisponibles as $unite) {
                    $unite->setLocataire($user);
                    $unite->setDateFinLocation($dateFin);
                    $this->em->persist($unite);
                }
            }

            // 3. VIDER LE PANIER
            foreach ($cart->getCartItems() as $item) {
                $this->em->remove($item);
            }

            $this->em->flush();
            $this->em->commit();

        } catch (RuntimeException $e) {
            $this->em->rollback();
            throw $e; // On renvoie l'erreur au Controller pour qu'il affiche le Flash
        }
    }
}
