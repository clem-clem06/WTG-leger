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
        // 1. Récupérer les offres
        $offres = $offreRepository->findAll();

        // 2. Calculer les statistiques
        $totalBaies = $baieRepository->count();
        $totalUnites = $uniteRepository->count();

        // Une unité est dispo si son champ 'locataire' est vide (NULL en base de données)
        $unitesDispo = $uniteRepository->count(['locataire' => null]);

        return $this->render('home/index.html.twig', [
            'offres' => $offres,
            'totalBaies' => $totalBaies,
            'totalUnites' => $totalUnites,
            'unitesDispo' => $unitesDispo,
        ]);
    }
}
