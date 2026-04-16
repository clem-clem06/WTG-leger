<?php

namespace App\Controller;

use App\Repository\OffreRepository;
use App\Repository\BaieRepository;
use App\Repository\UniteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(OffreRepository $offreRepository, BaieRepository $baieRepository, UniteRepository $uniteRepository): Response {
        if ($this->getUser() && !$this->isGranted('ROLE_CLIENT')) {
            return $this->render('home/blocked.html.twig', [
                'totalBaies' => $baieRepository->count(),
                'totalUnites' => $uniteRepository->count(),
                'unitesDispo' => $uniteRepository->count(['locataire' => null]),
            ]);
        }

        $offres = $offreRepository->findAll();
        $totalBaies = $baieRepository->count();
        $totalUnites = $uniteRepository->count();
        $unitesDispo = $uniteRepository->count(['locataire' => null]);

        return $this->render('home/index.html.twig', [
            'offres' => $offres,
            'totalBaies' => $totalBaies,
            'totalUnites' => $totalUnites,
            'unitesDispo' => $unitesDispo,
        ]);
    }
}
