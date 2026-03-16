<?php

namespace App\Controller;

use App\Repository\InterventionRepository;
use App\Repository\UniteRepository;
use App\Service\InterventionService;
use DateTime;
use DomainException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
final class ApiController extends AbstractController
{
    // ======================================================
    // 1. API : LISTER LES UNITÉS ET LEUR ÉTAT
    // ======================================================
    #[Route('/unites', name: 'unites_list', methods: ['GET'])]
    public function getUnites(UniteRepository $uniteRepository): JsonResponse
    {
        $unites = $uniteRepository->findAllWithBaieAndLocataire();

        $data = [];
        foreach ($unites as $unite) {

            // On vérifie si l'unité a un locataire ET si la date de fin n'est pas dépassée
            $maintenant = new DateTime();
            $estLouee = false;

            if ($unite->getLocataire() !== null && $unite->getDateFinLocation() > $maintenant) {
                $estLouee = true;
            }

            $data[] = [
                'id' => $unite->getId(),
                'numero' => $unite->getNumero(),
                'etat' => $unite->getEtat(),
                'disponible' => !$estLouee,
                'locataire_id' => $estLouee ? $unite->getLocataire()->getId() : null,
                'date_fin_location' => $unite->getDateFinLocation()?->format('Y-m-d H:i:s'),
                'baie_id' => $unite->getBaie()?->getId(),
            ];
        }

        return $this->json($data);
    }

    // ======================================================
    // 2. API : LISTER LES INTERVENTIONS
    // ======================================================
    #[Route('/interventions', name: 'interventions_list', methods: ['GET'])]
    public function getInterventions(InterventionRepository $interventionRepository): JsonResponse
    {
        $interventions = $interventionRepository->findAllWithUnites();

        $data = [];
        foreach ($interventions as $intervention) {

            $unitesAffectees = [];
            foreach ($intervention->getUnites() as $unite) {
                $unitesAffectees[] = $unite->getId();
            }

            $data[] = [
                'id' => $intervention->getId(),
                'type' => $intervention->getType(),
                'description' => $intervention->getDescription(),
                'dateDebut' => $intervention->getDateDebut()?->format('Y-m-d H:i:s'),
                'dateFin' => $intervention->getDateFin()?->format('Y-m-d H:i:s'),
                'unites_affectees' => $unitesAffectees
            ];
        }

        return $this->json($data);
    }

    // ======================================================
    // 3. API : DÉCLARER UNE INTERVENTION (POST)
    // ======================================================
    /**
     * @throws /JsonException
     */
    #[Route('/interventions', name: 'interventions_create', methods: ['POST'])]
    public function createIntervention(Request $request, InterventionService $interventionService): JsonResponse
    {
        try {
            // Lecture du JSON sécurisée
            $jsonData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // Le Service s'occupe de la base de données !
            $intervention = $interventionService->createIntervention($jsonData);

            return $this->json([
                'message' => 'L\'intervention a été déclarée avec succès !',
                'intervention_id' => $intervention->getId()
            ], 201);

        } catch (\JsonException) {
            return $this->json(['erreur' => 'Le format JSON envoyé est invalide.'], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json(['erreur' => $e->getMessage()], 400);
        } catch (DomainException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        }
    }
}
