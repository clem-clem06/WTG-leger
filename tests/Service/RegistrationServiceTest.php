<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests unitaires de l'inscription (hachage + rôle par défaut + persistance).
 */
class RegistrationServiceTest extends TestCase
{
    public function testLeMotDePasseEstHache(): void
    {
        $user = new User();
        $user->setEmail('nouveau@wtg.fr');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'MotDePasseEnClair123!')
            ->willReturn('HASHED');

        $em = $this->createStub(EntityManagerInterface::class);

        (new RegistrationService($em, $hasher))->registerUser($user, 'MotDePasseEnClair123!');

        $this->assertSame('HASHED', $user->getPassword());
    }

    public function testUserPersisteEtFlush(): void
    {
        $user = new User();
        $user->setEmail('nouveau@wtg.fr');

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('HASHED');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('flush');

        (new RegistrationService($em, $hasher))->registerUser($user, 'MotDePasseEnClair123!');
    }

    public function testRolesExistantsSontPreserves(): void
    {
        $user = new User();
        $user->setEmail('admin2@wtg.fr');
        $user->setRoles(['ROLE_ADMIN']);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('HASHED');

        $em = $this->createStub(EntityManagerInterface::class);

        (new RegistrationService($em, $hasher))->registerUser($user, 'MotDePasseEnClair123!');

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }
}
