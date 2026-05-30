<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Enum\UniteEtat;
use App\Repository\UniteRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CheckoutService
{
    public function __construct(private EntityManagerInterface $em, private UniteRepository $uniteRepository, private CartService $cartService)
    {
    }

    /**
     * Traite la commande de A à Z (Coffre-fort SQL).
     *
     * @throws \DateMalformedStringException
     */
    public function processCheckout(User $user, Cart $cart, ?string $fakeBankToken, ?string $last4, bool $isVirement = false): void
    {
        // 0. CONTRÔLE TARIFAIRE — on refuse de valider si les tarifs ont évolué
        // depuis l'ajout au panier (snapshot devenu obsolète). Le panier est mis
        // à jour, le client est renvoyé sur /cart pour re-vérifier le total.
        if ($this->cartService->refreshPricesIfOutdated($cart)) {
            throw new \RuntimeException('Les tarifs de votre panier ont été mis à jour. Merci de vérifier le nouveau total avant de valider la commande.');
        }

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
            $order->setStatus(OrderStatus::PENDING);

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

            if ($isVirement) {
                $payment->setStatus(PaymentStatus::PENDING);
                $payment->setGatewayResponse('Virement bancaire en attente de réception');
            } else {
                $payment->setStatus(PaymentStatus::COMPLETED);
                $tokenTrace = $fakeBankToken ? ' avec la carte finissant par '.$last4 : '';
                $payment->setGatewayResponse('Paiement réussi'.$tokenTrace);

                $order->setStatus(OrderStatus::PAID);
            }

            $this->em->persist($order);
            $this->em->persist($payment);

            // 2. ATTRIBUTION
            $totalUnitesRequises = 0;

            foreach ($cart->getCartItems() as $item) {
                $totalUnitesRequises += $item->getOffre()->getNombreUnites() * $item->getQuantity();
            }

            $unitesDisponibles = $this->uniteRepository->findAndLockAvailableUnites($totalUnitesRequises);

            if (count($unitesDisponibles) < $totalUnitesRequises) {
                throw new \RuntimeException('Désolé, notre stock est insuffisant pour valider l\'intégralité de votre panier.');
            }

            $uniteIndex = 0;
            foreach ($cart->getCartItems() as $item) {
                $unitesRequisesPourCetItem = $item->getOffre()->getNombreUnites() * $item->getQuantity();
                $dateFin = new \DateTime('+'.$item->getDureeMois().' months');

                for ($i = 0; $i < $unitesRequisesPourCetItem; ++$i) {
                    $unite = $unitesDisponibles[$uniteIndex];
                    $unite->setLocataire($user);
                    $unite->setDateFinLocation($dateFin);
                    $this->em->persist($unite);
                    if ($isVirement) {
                        $unite->setEtat(UniteEtat::EN_ATTENTE_PAIEMENT);
                    }

                    ++$uniteIndex;
                }
            }

            // 3. VIDER LE PANIER
            foreach ($cart->getCartItems() as $item) {
                $this->em->remove($item);
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\RuntimeException $e) {
            $this->em->rollback();
            throw $e; // On renvoie l'erreur au Controller pour qu'il affiche le Flash
        }
    }

    /**
     * Annule les commandes par virement de plus de 14 jours et libère les serveurs.
     */
    public function cleanExpiredVirements(): void
    {
        // 1. On calcule la date limite (il y a 14 jours)
        $limitDate = new \DateTimeImmutable('-14 days');

        // 2. On cherche toutes les commandes "pending" plus vieilles que 14 jours
        $expiredOrders = $this->em->getRepository(Order::class)->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt <= :limit')
            ->setParameter('status', OrderStatus::PENDING)
            ->setParameter('limit', $limitDate)
            ->getQuery()
            ->getResult();

        foreach ($expiredOrders as $order) {
            // A. On passe la commande en annulée
            $order->setStatus(OrderStatus::CANCELLED);

            // B. On passe ses paiements en annulés
            foreach ($order->getPayments() as $payment) {
                if (PaymentStatus::PENDING === $payment->getStatus()) {
                    $payment->setStatus(PaymentStatus::CANCELLED);
                    $payment->setGatewayResponse('Annulé : Délai de 14 jours dépassé.');
                }
            }

            // C. On récupère les unités bloquées de ce client pour les libérer
            $user = $order->getUser();
            $unitesToFreeCount = 0;
            foreach ($order->getOrderItems() as $item) {
                $unitesToFreeCount += $item->getOffre()->getNombreUnites() * $item->getQuantity();
            }

            // On cherche uniquement ses unités qui étaient "en attente de paiement"
            // (constante partagée → plus de bug de casse avec l'écriture)
            $unitesToFree = $this->uniteRepository->findBy([
                'locataire' => $user,
                'etat' => UniteEtat::EN_ATTENTE_PAIEMENT,
            ], null, $unitesToFreeCount);

            foreach ($unitesToFree as $unite) {
                $unite->setLocataire(null);
                $unite->setDateFinLocation(null);
                $unite->setEtat(UniteEtat::OK);
            }
        }

        // Si on a nettoyé des choses, on sauvegarde en base de données
        if (count($expiredOrders) > 0) {
            $this->em->flush();
        }
    }
}
