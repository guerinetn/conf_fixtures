<?php

namespace App\Security;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\IdentityException;
use App\Exception\VelException;
use App\Service\IdentityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

use function Symfony\Component\Clock\now;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly IdentityService $identityService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return '' !== $request->headers->get('authorization', '');
    }

    /**
     * @throws IdentityException
     * @throws \Doctrine\DBAL\Exception
     * @throws VelException
     * @throws \JsonException
     */
    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('authorization', '');
        if ('' === $token) {
            throw new AuthenticationException('No token available');
        }

        try {
            $idpId = $this->identityService->validateToken($token);
        } catch (
            IdentityException|
            ClientExceptionInterface|
            RedirectionExceptionInterface|
            ServerExceptionInterface|
            TransportExceptionInterface $e
        ) {
            throw new AuthenticationException($e->getMessage(), previous: $e);
        }
        $roles = $this->identityService->getRoleFromToken($token);
        if (!in_array(Role::ROLE_ANONYMOUS->name, $roles, true)) {
            $this->identityService->createOrUpdateUserFromToken($token, $roles);
        }

        return new SelfValidatingPassport(new UserBadge($idpId));
    }

    /**
     * @throws IdentityException
     * @throws \DateMalformedStringException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $roles = $this->identityService->getRoleFromToken($request->headers->get('authorization', ''));

        /** @var User $user */
        $user = $token->getUser();
        $user->setRoles($roles);
        $user->setLastConnectedAt(now());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessage()],
            Response::HTTP_FORBIDDEN
        );
    }
}
