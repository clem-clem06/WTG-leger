<?php

namespace App\Controller;

use App\Repository\OffreRepository;
use App\Repository\BaieRepository;
use App\Repository\UniteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        OffreRepository $offreRepository,
        BaieRepository $baieRepository,
        UniteRepository $uniteRepository
    ): Response {
        // 1. Récupérer les offres
        $offres = $offreRepository->findAll();

        // 2. Calculer les statistiques
        $totalBaies = $baieRepository->count([]);
        $totalUnites = $uniteRepository->count([]);

        // On considère qu'une unité est disponible si elle n'a pas encore de nom défini par un client
        $unitesDispo = $uniteRepository->count(['nom' => null]);

        return $this->render('home/index.html.twig', [
            'offres' => $offres,
            'totalBaies' => $totalBaies,
            'totalUnites' => $totalUnites,
            'unitesDispo' => $unitesDispo,
        ]);
    }
}
