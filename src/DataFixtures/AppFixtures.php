<?php

namespace App\DataFixtures;

use App\Entity\Baie;
use App\Entity\Unite;
use App\Entity\Offre;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // ════════════════════════════════════════════════════════════
        //  1. OFFRES COMMERCIALES  (prix en centimes : 100€ = 10000)
        // ════════════════════════════════════════════════════════════
        $offresData = [
            // Base : 100€/mois | Annuel : (100 × 12) - 10% = 1080€
            ['nom' => 'Base',       'unites' => 1,  'prixMensuel' => 10000,   'prixAnnuel' => 108000],
            // Start-up : 900€/mois | Annuel : (900 × 12) - 10% = 9720€
            ['nom' => 'Start-up',   'unites' => 10, 'prixMensuel' => 90000,   'prixAnnuel' => 972000],
            // PME : 1680€/mois | Annuel : (1680 × 12) - 10% = 18144€
            ['nom' => 'PME',        'unites' => 21, 'prixMensuel' => 168000,  'prixAnnuel' => 1814400],
            // Entreprise : 2940€/mois | Annuel : (2940 × 12) - 10% = 31752€
            ['nom' => 'Entreprise', 'unites' => 42, 'prixMensuel' => 294000,  'prixAnnuel' => 3175200],
        ];

        foreach ($offresData as $data) {
            $offre = new Offre();
            $offre->setNom($data['nom']);
            $offre->setNombreUnites($data['unites']);
            $offre->setPrixMensuel($data['prixMensuel']);
            $offre->setPrixAnnuel($data['prixAnnuel']);
            $manager->persist($offre);
        }

        // ════════════════════════════════════════════════════════════
        //  2. BAIES ET UNITÉS  (30 baies × 42 unités = 1260 unités)
        // ════════════════════════════════════════════════════════════
        for ($b = 1; $b <= 30; $b++) {
            $baie = new Baie();
            // str_pad → ajoute des zéros : 1 → "001"  donc "B001"
            $baie->setReference('B' . str_pad((string)$b, 3, '0', STR_PAD_LEFT));
            $manager->persist($baie);

            for ($u = 1; $u <= 42; $u++) {
                $unite = new Unite();
                $unite->setNumero('U' . str_pad((string)$u, 2, '0', STR_PAD_LEFT));
                $unite->setEtat('OK');
                $unite->setBaie($baie);
                $manager->persist($unite);
            }
        }

        // ════════════════════════════════════════════════════════════
        //  3. UTILISATEURS
        // ════════════════════════════════════════════════════════════

        // ── Administrateur ──────────────────────────────────────────
        $admin = new User();
        $admin->setEmail('admin@wtg.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        // hashPassword encode le mot de passe avec BCrypt
        // (même algo que Spring Security BCryptPasswordEncoder)
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        // ── Comptable ───────────────────────────────────────────────
        $comptable = new User();
        $comptable->setEmail('comptable@wtg.fr');
        $comptable->setRoles(['ROLE_COMPTABLE']);
        $comptable->setPassword($this->passwordHasher->hashPassword($comptable, 'Comptable123!'));
        $manager->persist($comptable);

        // ── Client de test ──────────────────────────────────────────
        // Ce user a ROLE_USER → il N'A PAS accès à l'application Java
        // Il peut seulement se connecter au site Symfony (le léger)
        $client = new User();
        $client->setEmail('client@htmail.fr');
        $client->setRoles(['ROLE_CLIENT']);
        $client->setPassword($this->passwordHasher->hashPassword($client, 'Client123!'));
        $client->setApiToken('WTG-SECRET-KEY-2026');
        $manager->persist($client);

        // Envoie tout en BDD en une seule fois
        $manager->flush();
    }
}
