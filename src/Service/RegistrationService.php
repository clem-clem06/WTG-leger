<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class RegistrationService
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    /**
     * S'occupe de hacher le mot de passe, définir les rôles et sauvegarder le nouvel utilisateur.
     */
    public function registerUser(User $user, string $plainPassword): void
    {
        // 1. Hachage du mot de passe
        $user->setPassword(
            $this->userPasswordHasher->hashPassword($user, $plainPassword)
        );

        // 2. Attribution du rôle par défaut (sécurité)
        if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }

        // 3. Sauvegarde en base
        $this->em->persist($user);
        $this->em->flush();
    }
}
