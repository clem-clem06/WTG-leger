<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\UniteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'app_customer')]
    #[IsGranted('ROLE_CLIENT')]
    public function index(OrderRepository $orderRepository, UniteRepository $uniteRepository): Response
    {
        $user = $this->getUser();

        return $this->render('customer/index.html.twig', [
            'user' => $user,
            'orders' => $orderRepository->findDashboardOrders($user),
            'unites' => $uniteRepository->findDashboardUnites($user),
        ]);
    }

    /**
     * Vue JSON navigable des unités de l'utilisateur connecté.
     *
     * cette route est sous le firewall principal : elle s'ouvre directement
     * dans le navigateur grâce à la session de l'utilisateur connecté.
     */
    #[Route('/customer/unites.json', name: 'app_customer_unites_json', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function unitesJson(UniteRepository $uniteRepository): JsonResponse
    {
        $user = $this->getUser();

        $data = [];
        foreach ($uniteRepository->findUserUnitesWithBaie($user) as $unite) {
            $data[] = [
                'id' => $unite->getId(),
                'numero' => $unite->getNumero(),
                'etat' => $unite->getEtat(),
                'baie' => $unite->getBaie() ? $unite->getBaie()->getReference() : null,
                'dateFinLocation' => $unite->getDateFinLocation() ? $unite->getDateFinLocation()->format('d/m/Y') : null,
            ];
        }

        // Slashes non échappés + indentation pour une lecture brute agréable.
        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        ]);
    }
}
