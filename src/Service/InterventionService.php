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
        if (!isset($jsonData['type'], $jsonData['description'], $jsonData['unites_ids']) || !is_array($jsonData['unites_ids'])) {
            throw new InvalidArgumentException('Données invalides. Les champs "type", "description" et "unites_ids" (tableau) sont obligatoires.');
        }

        // 2. Création
        $intervention = new Intervention();
        $intervention->setType($jsonData['type']);
        $intervention->setDescription($jsonData['description']);
        $intervention->setDateDebut(new DateTime());

        // 3. Liaison avec les serveurs en panne
        foreach ($jsonData['unites_ids'] as $uniteId) {
            $unite = $this->uniteRepository->find($uniteId);
            if ($unite) {
                $intervention->addUnite($unite);
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
