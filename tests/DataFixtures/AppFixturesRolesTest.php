<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AppFixturesRolesTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $fixture = $container->get(AppFixtures::class);
        $executor = new ORMExecutor($this->em, new ORMPurger($this->em));
        $executor->execute([$fixture]);
    }

    public function testAdminHasRoleAdmin(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@wtg.fr']);
        $this->assertNotNull($user, 'admin@wtg.fr doit exister en base');
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testComptableHasRoleComptable(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'comptable@wtg.fr']);
        $this->assertNotNull($user, 'comptable@wtg.fr doit exister en base');
        $this->assertContains('ROLE_COMPTABLE', $user->getRoles());
    }

    public function testClientHasRoleClient(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'client@htmail.fr']);
        $this->assertNotNull($user, 'client@htmail.fr doit exister en base');
        $this->assertContains('ROLE_CLIENT', $user->getRoles());
    }

    public function testClientHasApiToken(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'client@htmail.fr']);
        $this->assertNotNull($user, 'client@htmail.fr doit exister en base');
        $this->assertNotEmpty($user->getApiToken(), 'client@htmail.fr doit avoir un apiToken');
    }
}
