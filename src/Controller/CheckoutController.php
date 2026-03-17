<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CheckoutType;
use App\Repository\CartRepository;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CheckoutService;
use App\Service\PaymentService;
use Throwable;
use Psr\Log\LoggerInterface;
use App\Repository\CardRepository;

#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(CartRepository $cartRepository, CardRepository $cardRepository,Request $request, PaymentService $paymentService, CheckoutService $checkoutService, LoggerInterface $logger): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->redirectToRoute('app_cart');
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // On récupère les données sécurisées du formulaire
                $data = $form->getData();

                // 1. Le PaymentService s'occupe de la carte (On lui passe les données du form !)
                [$fakeBankToken, $last4] = $paymentService->processCard($data, $user);

                // 2. Le CheckoutService s'occupe de générer la commande et verrouiller le stock
                $checkoutService->processCheckout($user, $cart, $fakeBankToken, $last4);

                $this->addFlash('success', 'Paiement réussi ! Vos unités ont été attribuées.');
                return $this->redirectToRoute('app_customer');

            } catch (InvalidArgumentException $e) {
                // ERREUR DE CARTE -> On reste sur la page et on affiche l'erreur
                $this->addFlash('danger', $e->getMessage());
            } catch (RuntimeException $e) {
                // ERREUR DE STOCK -> On renvoie au panier
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('app_cart');
            } catch (Throwable $e) {
                // ERREUR CRITIQUE -> Log + Message safe
                $logger->error('ERREUR CRITIQUE PAIEMENT : ' . $e->getMessage());
                $this->addFlash('danger', 'Une erreur technique inattendue est survenue. Aucun prélèvement n\'a été effectué. Veuillez réessayer plus tard.');
                return $this->redirectToRoute('app_cart');
            }
        }

        $savedCards = $cardRepository->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'savedCards' => $savedCards,
            'checkoutForm' => $form->createView(),
        ]);
    }
}
