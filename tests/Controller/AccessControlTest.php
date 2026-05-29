<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessControlTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        // Le kernel ne doit être booté qu'une seule fois : on crée le client ici
        // et les tests le réutilisent (Symfony 8 interdit un second createClient()).
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);

        $fixture = $container->get(AppFixtures::class);
        $executor = new ORMExecutor($this->em, new ORMPurger($this->em));
        $executor->execute([$fixture]);
    }

    // ── CartController ────────────────────────────────────────────────

    public function testCartRedirectsWhenNotLoggedIn(): void
    {
        $this->client->request('GET', '/cart');

        $this->assertResponseRedirects('/login');
    }

    public function testCartForbiddenForAdmin(): void
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@wtg.fr']);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/cart');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCartAccessibleForClient(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'client@htmail.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/cart');

        $this->assertResponseIsSuccessful();
    }

    // ── CheckoutController ────────────────────────────────────────────

    public function testCheckoutRedirectsWhenNotLoggedIn(): void
    {
        $this->client->request('GET', '/checkout');

        $this->assertResponseRedirects('/login');
    }

    public function testCheckoutForbiddenForAdmin(): void
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@wtg.fr']);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/checkout');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCheckoutRedirectsToCartWhenEmpty(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'client@htmail.fr']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/checkout');

        // Panier vide → redirige vers /cart
        $this->assertResponseRedirects('/cart');
    }
}
