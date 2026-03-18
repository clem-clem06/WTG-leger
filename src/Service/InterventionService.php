<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Repository\UniteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;

readonly class InterventionService
{
    public function __construct(private EntityManagerInterface $em, private UniteRepository $uniteRepository)
    {
    }

    /**
     * Valide le JSON et crée la panne dans le Datacenter
     */
    public function createIntervention(array $jsonData): Intervention
    {
        // 1. Le Vigile
        if (!isset($jsonData['type'], $jsonData['etat'], $jsonData['description'], $jsonData['unites_affectees']) || !is_array($jsonData['unites_affectees'])) {
            throw new InvalidArgumentException('Données invalides. Les champs "etat", "type", "description" et "unites_affectees" (tableau) sont obligatoires.');
        }

        // 2. Création
        $intervention = new Intervention();
        $intervention->setType($jsonData['type']);
        $intervention->setDescription($jsonData['description']);
        $intervention->setEtat($jsonData['etat']);

        // 3. Gestion de la Date de Début
        if (isset($jsonData['dateDebut'])) {
            $dateDebut = DateTime::createFromFormat('d/m/Y H:i:s', $jsonData['dateDebut']);
            if (!$dateDebut) {
                throw new InvalidArgumentException('Le format de la date de début est invalide. Utilisez JJ/MM/AAAA HH:MM:SS');
            }
            $intervention->setDateDebut($dateDebut);
        } else {
            $intervention->setDateDebut(new DateTime());
        }

        // 4. Gestion de la Date de Fin
        if (isset($jsonData['dateFin'])) {
            $dateFin = DateTime::createFromFormat('d/m/Y H:i:s', $jsonData['dateFin']);
            if (!$dateFin) {
                throw new InvalidArgumentException('Le format de la date de fin est invalide. Utilisez JJ/MM/AAAA HH:MM:SS');
            }
            $intervention->setDateFin($dateFin);
        }

        // 5. Liaison avec les serveurs en panne
        foreach ($jsonData['unites_affectees'] as $uniteId) {
            $unite = $this->uniteRepository->find($uniteId);
            if ($unite) {
                $intervention->addUnite($unite);
                $unite->setEtat($jsonData['etat']);
            }
        }

        if ($intervention->getUnites()->isEmpty()) {
            throw new DomainException('Aucune unité correspondante trouvée dans le datacenter.');
        }

        $this->em->persist($intervention);
        $this->em->flush();

        return $intervention;
    }
}
