<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ApiTokenHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Tests unitaires de l'authentification par token API (firewall stateless).
 */
class ApiTokenHandlerTest extends TestCase
{
    public function testTokenValideRenvoieUnUserBadge(): void
    {
        $user = new User();
        $user->setEmail('client@hotmail.fr');
        $user->setApiToken('WTG-SECRET-KEY-TEST');

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiToken' => 'WTG-SECRET-KEY-TEST'])
            ->willReturn($user);

        $handler = new ApiTokenHandler($repository);
        $badge = $handler->getUserBadgeFrom('WTG-SECRET-KEY-TEST');

        $this->assertInstanceOf(UserBadge::class, $badge);
        $this->assertSame('client@hotmail.fr', $badge->getUserIdentifier());
    }

    public function testTokenInvalideLeveUneException(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiToken' => 'TOKEN-BIDON'])
            ->willReturn(null);

        $handler = new ApiTokenHandler($repository);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('TOKEN-BIDON');
    }
}
