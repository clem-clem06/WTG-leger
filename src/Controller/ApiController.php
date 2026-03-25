<?php

namespace App\Controller;

use App\Repository\UniteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ApiController extends AbstractController
{
    #[Route('/api/unites', name: 'api_unites', methods: ['GET'])]
    public function getMesUnites(UniteRepository $uniteRepository): JsonResponse
    {
        $user = $this->getUser();

        $unites = $uniteRepository->findUserUnitesWithBaie($user);

        $data = [];
        foreach ($unites as $unite) {
            $data[] = [
                'id' => $unite->getId(),
                'numero' => $unite->getNumero(),
                'etat' => $unite->getEtat(),
                'baie' => $unite->getBaie() ? $unite->getBaie()->getReference() : null,
                'dateFinLocation' => $unite->getDateFinLocation() ? $unite->getDateFinLocation()->format('d/m/Y') : null,
            ];
        }

        return $this->json($data);
    }
}
