<?php

namespace App\Service;

use App\Exception\IdentityException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdentityMgmtService
{
    private ?string $managementToken = null;

    public function __construct(
        HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(env: 'IDP_HOST')]
        string $idpHost,
        #[Autowire(env: 'IDP_BASE_PATH')]
        private readonly string $idpBasePath,
        #[Autowire(env: 'IDP_REALM')]
        private readonly string $idpRealm,
        #[Autowire(env: 'IDP_MANAGEMENT_SECRET')]
        private readonly string $idpManagementSecret,
        #[Autowire(env: 'IDP_MANAGEMENT_USER')]
        private readonly string $idpManagementUser,
    ) {
        $this->httpClient = $httpClient->withOptions(['base_uri' => $idpHost, 'no_proxy' => 'mar_idp']);
    }
    /**
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createRole(array $roleToCreate): string
    {
        $postRoleResponse = $this->doManagementRequest('POST', '/roles', $roleToCreate);

        if (!in_array($postRoleResponse->getStatusCode(), [201, 409], true)) {
            throw new IdentityException('Une erreur est survenue lors de la création d\'un nouveau rôle keycloak', 500);
        }

        return 'Pas d\'erreur lors de la création du rôle';
    }


    /**
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function doManagementRequest(string $method, string $route, array $body = []): ResponseInterface
    {
        $managementToken = $this->fetchManagementAccessToken();

        return $this->httpClient->request(
            $method,
            $this->idpBasePath.'/admin/realms/'.$this->idpRealm.$route,
            [
                'headers' => ['Content-Type' => 'application/json'],
                'auth_bearer' => $managementToken,
                'json' => $body,
            ],
        );
    }


    /**
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchManagementAccessToken(): string
    {
        if (is_null($this->managementToken)) {
            $response = $this->httpClient->request(
                'POST',
                $this->idpBasePath.'/realms/master/protocol/openid-connect/token',
                [
                    'auth_basic' => [$this->idpManagementUser, $this->idpManagementSecret],
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => ['grant_type' => 'client_credentials'],
                ],
            );

            $this->managementToken = (string) json_decode(
                $response->getContent(),
                false,
                512,
                JSON_THROW_ON_ERROR,
            )->access_token;
        }

        return $this->managementToken;
    }

}
