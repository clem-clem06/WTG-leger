<?php

namespace App\DataFixtures;

use App\Entity\Baie;
use App\Entity\Unite;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des 30 baies (B001 à B030)
        for ($b = 1; $b <= 30; $b++) {
            $baie = new Baie();
            // str_pad ajoute les zéros pour formater en B001, B015, etc.
            $referenceBaie = 'B' . str_pad((string)$b, 3, '0', STR_PAD_LEFT);
            $baie->setReference($referenceBaie);

            $manager->persist($baie);

            // Création des 42 unités pour chaque baie (U01 à U42)
            for ($u = 1; $u <= 42; $u++) {
                $unite = new Unite();
                $numeroUnite = 'U' . str_pad((string)$u, 2, '0', STR_PAD_LEFT);

                $unite->setNumero($numeroUnite);
                $unite->setEtat('OK'); // État par défaut
                $unite->setBaie($baie);

                // Les champs nom, type et couleur restent vides (null)
                // Le client les remplira plus tard depuis son espace

                $manager->persist($unite);
            }
        }

        // On envoie les 30 baies et les 1260 unités en base de données d'un seul coup
        $manager->flush();
    }
}
