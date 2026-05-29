<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de l'entité User (logique de rôles + sécurité de sérialisation).
 */
class UserTest extends TestCase
{
    public function testGetRolesContientToujoursRoleUser(): void
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetRolesAjouteRoleUserAuxRolesExistants(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesDedoublonne(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        // Une seule occurrence de chaque rôle malgré les doublons + l'ajout auto.
        $this->assertSame(array_values(array_unique($roles)), array_values($roles));
        $this->assertSame(1, count(array_keys($roles, 'ROLE_ADMIN', true)));
        $this->assertSame(1, count(array_keys($roles, 'ROLE_USER', true)));
    }

    public function testGetUserIdentifierRenvoieEmail(): void
    {
        $user = new User();
        $user->setEmail('client@hotmail.fr');

        $this->assertSame('client@hotmail.fr', $user->getUserIdentifier());
    }

    public function testSerializeExclutLeTokenApi(): void
    {
        $user = new User();
        $user->setEmail('client@hotmail.fr');
        $user->setPassword('hash_de_mot_de_passe');
        $user->setApiToken('WTG-SECRET-KEY-TEST');

        $data = $user->__serialize();

        // L'apiToken ne doit jamais finir en session.
        $cleApiToken = "\0".User::class."\0apiToken";
        $this->assertArrayNotHasKey($cleApiToken, $data);

        // Aucune valeur sérialisée ne doit contenir le token en clair.
        $this->assertNotContains('WTG-SECRET-KEY-TEST', $data, '', false);
    }

    public function testSerializeHacheLeMotDePasse(): void
    {
        $user = new User();
        $user->setEmail('client@hotmail.fr');
        $user->setPassword('hash_de_mot_de_passe');

        $data = $user->__serialize();

        // Le mot de passe haché est remplacé par un CRC32C (pas le hash réel).
        $clePassword = "\0".User::class."\0password";
        $this->assertArrayHasKey($clePassword, $data);
        $this->assertSame(hash('crc32c', 'hash_de_mot_de_passe'), $data[$clePassword]);
        $this->assertNotSame('hash_de_mot_de_passe', $data[$clePassword]);
    }
}
