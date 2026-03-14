<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

readonly class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private UserRepository $userRepository) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        // On cherche un utilisateur qui possède ce token exact
        $user = $this->userRepository->findOneBy(['apiToken' => $accessToken]);

        if (null === $user) {
            throw new BadCredentialsException('Token API invalide.');
        }

        // Si on le trouve, on connecte l'utilisateur "silencieusement" pour cette requête
        return new UserBadge($user->getUserIdentifier());
    }
}
