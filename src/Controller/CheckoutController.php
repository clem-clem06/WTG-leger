<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CheckoutService;
use App\Service\PaymentService;
use Throwable;

#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
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

    #[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, CartRepository $cartRepository, PaymentService $paymentService, CheckoutService $checkoutService, $logger): Response
    {

        /** @var User $user */
        $user = $this->getUser();

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return $this->redirectToRoute('app_cart');
        }

        try {
            // 1. Le PaymentService s'occupe de valider/créer la carte
            [$fakeBankToken, $last4] = $paymentService->processCard($request, $user);

            // 2. Le CheckoutService s'occupe de générer la commande et de verrouiller le stock
            $checkoutService->processCheckout($user, $cart, $fakeBankToken, $last4);

            // Si aucune erreur n'a explosé, c'est un succès !
            $this->addFlash('success', 'Paiement réussi ! Vos unités ont été attribuées.');
            return $this->redirectToRoute('app_home'); // TODO: rediriger vers espace client

        } catch (InvalidArgumentException $e) {
            // 1. ERREUR DE CARTE -> MESSAGE SAFE
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_checkout');

        } catch (DomainException $e) {
            // 2. ERREUR DE STOCK (Notre \DomainException) -> MESSAGE SAFE
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_cart');

        } catch (Throwable $e) {
            // On écrit la vraie erreur brute dans le fichier de logs du serveur (var/log/dev.log)
            $logger->error('ERREUR CRITIQUE PAIEMENT : ' . $e->getMessage());

            // Et on affiche un message poli et générique au client
            $this->addFlash('danger', 'Une erreur technique inattendue est survenue. Aucun prélèvement n\'a été effectué. Veuillez réessayer plus tard.');
            return $this->redirectToRoute('app_cart');
        }
    }
}
