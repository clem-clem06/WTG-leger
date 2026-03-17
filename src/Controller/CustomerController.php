<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\UniteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'app_customer')]
    public function index(OrderRepository $orderRepository, UniteRepository $uniteRepository): Response
    {
        $user = $this->getUser();

        return $this->render('customer/index.html.twig', [
            'user' => $user,
            'orders' => $orderRepository->findDashboardOrders($user),
            'unites' => $uniteRepository->findDashboardUnites($user),
        ]);
    }
}
