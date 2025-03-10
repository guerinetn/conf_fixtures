<?php

namespace App\Service;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\IdentityException;
use App\Exception\VelException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdentityService
{
    private HttpClientInterface $httpClient;
    private ?string $token = null;

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
        #[Autowire(env: 'IDP_USER')]
        private readonly string $idpLogin,
        #[Autowire(env: 'IDP_SECRET')]
        private readonly string $idpPassword,
        #[Autowire(env: 'IDP_FRONTEND_CLIENT_ID')]
        private readonly string $frontendClientId,
        #[Autowire(env: 'FRONTEND_URL')]
        private readonly string $frontendUrl,
    ) {
        $this->httpClient = $httpClient->withOptions(['base_uri' => $idpHost, 'no_proxy' => 'mar_idp']);
    }

    /**
     * @throws IdentityException
     * @throws \RuntimeException
     * @throws \JsonException
     */
    public function createUser(User $user, string $password): string
    {
        $body = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'username' => $user->getEmail(),
            'enabled' => true,
            'emailVerified' => '',
            'groups' => [],
            'credentials' => [
                [
                    'type' => 'password',
                    'value' => $password,
                    'temporary' => false,
                ],
            ],
        ];

        try {
            $response = $this->request('POST', '/users', $body);

            if (201 === $response->getStatusCode()) {
                $location = $response->getHeaders()['location'][0];
                $parts = explode('/', $location);
                $idpUserId = end($parts);
                $this->forceSendEmail($idpUserId);

                return $idpUserId;
            }

            throw new IdentityException($response->toArray(false)['errorMessage']);
        } catch (
            ClientExceptionInterface|
            RedirectionExceptionInterface|
            ServerExceptionInterface|
            TransportExceptionInterface|
            DecodingExceptionInterface $e
        ) {
            $this->logger->error(
                sprintf(
                    'Idp Create User  - exception : %s  /code : %s/ message : %s',
                    $e::class,
                    $e->getCode(),
                    $e->getMessage(),
                ),
            );
            throw new \RuntimeException('Internal Server Error', previous: $e);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws IdentityException
     * @throws \JsonException
     * */
    private function forceSendEmail(string $idpUserId): void
    {
        $clientId = $this->frontendClientId;
        $redirectUri = urlencode($this->frontendUrl.'/account/login');

        $this->request(
            'PUT',
            '/users/'.$idpUserId."/execute-actions-email?client_id=$clientId&redirect_uri=$redirectUri",
        );
    }

    /**
     * @throws IdentityException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    protected function fetchAccessToken(): string
    {
        if (is_null($this->token)) {
            $response = $this->httpClient->request(
                'POST',
                $this->idpBasePath.'/realms/'.$this->idpRealm.'/protocol/openid-connect/token',
                [
                    'auth_basic' => [$this->idpLogin, $this->idpPassword],
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => ['grant_type' => 'client_credentials'],
                ],
            );
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                $context = [$response->getStatusCode().' : '.$response->getContent(false)];
                throw new IdentityException(message: 'Error while fetch token', context: $context);
            }
            $this->token = (string) json_decode($response->getContent(), flags: JSON_THROW_ON_ERROR)->access_token;
        }

        return $this->token;
    }

    /**
     * @throws IdentityException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    protected function request(string $method, string $route, array $body = []): ResponseInterface
    {
        $token = $this->fetchAccessToken();

        return $this->httpClient->request(
            $method,
            $this->idpBasePath.'/admin/realms/'.$this->idpRealm.$route,
            [
                'headers' => ['Content-Type' => 'application/json'],
                'auth_bearer' => $token,
                'json' => $body,
            ],
        );
    }

    /**
     * @throws IdentityException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function validateToken(string $token): string
    {
        $token = str_replace('/Bearer /', '', $token);

        $response = $this->httpClient->request(
            'GET',
            $this->idpBasePath.'/realms/'.$this->idpRealm.'/protocol/openid-connect/userinfo',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'auth_bearer' => $token,
            ],
        );

        $decodeResponse = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        if (200 === $response->getStatusCode() && null !== $decodeResponse && isset($decodeResponse['sub'])) {
            return $decodeResponse['sub'];
        }
        throw new IdentityException('Invalid token');
    }

    /**
     * @throws IdentityException
     */
    public function getRoleFromToken(string $idpToken): array
    {
        $decodedToken = $this->decodeToken($idpToken);
        if (isset($decodedToken['realm_access']['roles'])) {
            $roles = $decodedToken['realm_access']['roles'];

            foreach ($roles as $role) {
                if (null !== Role::tryFrom($role)) {
                    $marRoles[] = Role::from($role)->name;
                }
            }

            return $marRoles ?? throw new IdentityException(message: 'No role map available for this user', code: Response::HTTP_UNAUTHORIZED);
        }
        $this->logger->error(
            sprintf(
                'user %s has no accepted role - realm access : %s',
                $decodedToken['sub'],
                var_export($decodedToken['realm_access'], true),
            ),
        );
        throw new IdentityException(message: 'No role from user', code: Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws \App\Exception\VelException
     * @throws IdentityException
     * @throws \Doctrine\DBAL\Exception
     */
    public function createOrUpdateUserFromToken(string $idpToken, array $roles): User
    {
        if (in_array(Role::ROLE_ANONYMOUS->name, $roles, true)) {
            throw new IdentityException('Invalid Role', Response::HTTP_FORBIDDEN);
        }
        try {
            $decodedToken = $this->decodeToken($idpToken);
            $this->entityManager->getConnection()->beginTransaction();
            $user = $this->entityManager->getRepository(User::class)
                ->loadUserByIdentifier($decodedToken['sub']);
            if (null === $user) {
                $user = new User();
                $user->setUuid($decodedToken['sub']);
            }

            $user->setRoles($roles);
            $user->setEmail($decodedToken['email']);
            $user->setFirstName($decodedToken['given_name']);
            $user->setLastName($decodedToken['family_name']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (IdentityException $idpException) {
            throw new VelException(context: ['Intervenant Create or Update', $idpException->getMessage()], code: Response::HTTP_FORBIDDEN, previous: $idpException);
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw new VelException(context: ['Intervenant Create or Update', $exception->getMessage()], code: Response::HTTP_INTERNAL_SERVER_ERROR, previous: $exception);
        }

        return $user;
    }

    /**
     * Convert a token using a base64url (URL-safe Base64) encoding to standard base64.
     *
     * @param string $bearerToken A base64url encoded string with URL-safe characters (-_ and no padding)
     *                            The token is converted in a Base64 encoded string
     *                            with standard characters (+/) and padding (=), when needed.
     *                            Then the standard base64 string is decoded and finally convert json to array.
     *
     * @return array json_decoded token
     *
     * @throws IdentityException
     *
     * @see https://www.rfc-editor.org/rfc/rfc4648
     */
    private function decodeToken(string $bearerToken): array
    {
        $payload = explode('.', $bearerToken);
        try {
            if (isset($payload[1])) {
                return json_decode(
                    base64_decode(
                        str_pad(
                            string: strtr($payload[1], '-_', '+/'),
                            length: strlen($payload[1]) % 4,
                            pad_string: '=',
                        ),
                    ), true, 512, JSON_THROW_ON_ERROR,
                );
            }
            throw new IdentityException(message: 'Identity Decode token - invalid Payload', code: Response::HTTP_UNAUTHORIZED);
        } catch (\JsonException $jsonException) {
            throw new IdentityException(message: 'Identity Decode token - Invalid Token', code: Response::HTTP_NOT_ACCEPTABLE, previous: $jsonException);
        }
    }

    /**
     * @throws IdentityException
     * @throws \JsonException
     */
    public function deleteUser(string $idIdp): void
    {
        try {
            $response = $this->request('DELETE', "/users/$idIdp");
            if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()
                && Response::HTTP_NOT_FOUND !== $response->getStatusCode()
            ) {
                throw new IdentityException($response->toArray(false)['errorMessage']);
            }
        } catch (
            ClientExceptionInterface|
            RedirectionExceptionInterface|
            ServerExceptionInterface|
            TransportExceptionInterface|
            DecodingExceptionInterface $e
        ) {
            $this->logger->error(
                sprintf(
                    'Idp Create User  - exception : %s  /code : %s/ message : %s',
                    $e::class,
                    $e->getCode(),
                    $e->getMessage(),
                ),
            );
            throw new \RuntimeException('Internal Server Error', previous: $e);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws IdentityException
     * @throws \JsonException
     */
    public function updateUserInformations(Demandeur $user, string $firstname, string $lastName): void
    {
        $response = $this->request(
            'PUT',
            '/users/'.$user->getIdpId(),
            ['firstName' => $firstname, 'lastName' => $lastName],
        );
        if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
            $context = [$response->getStatusCode().' : '.$response->getContent(false)];
            throw new IdentityException(message: 'Erreur lors de la mise à jour des informations', context: $context);
        }
    }

    /**
     * @throws IdentityException
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function updateUserEmail(Demandeur $demandeur, string $email): void
    {
        $response = $this->request(
            'PUT',
            '/users/'.$demandeur->getIdpId(),
            [
                'email' => $email,
                'emailVerified' => false,
                'requiredActions' => ['VERIFY_EMAIL'],
            ],
        );
        if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
            $context = [$response->getStatusCode().' : '.$response->getContent(false)];
            throw new IdentityException(message: "Erreur lors de la mise à jour de l'email", context: $context);
        }
    }

    /**
     * @throws IdentityException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function userExistsWithEmail(string $email): bool
    {
        $response = $this->request(
            'GET',
            '/users?exact=true&email='.$email,
        );
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $context = [$response->getStatusCode().' : '.$response->getContent(false)];
            throw new IdentityException(message: "Erreur lors de la recherche d'existence d'email", context: $context);
        }

        return !empty(json_decode($response->getContent(false), flags: JSON_THROW_ON_ERROR));
    }

    /**
     * @throws \App\Exception\DemandeException
     * @throws IdentityException
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function updateUserPassword(array $payload, Demandeur $user): void
    {
        $context = [
            'type' => 'password',
            'temporary' => 'false',
            'value' => $payload['newPassword'],
        ];

        $response = $this->request(
            'PUT',
            '/users/'.$user->getIdpId().'/reset-password',
            $context,
        );
        if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
            throw new DemandeException(errors: ['message' => 'Le nouveau mot de passe ne respecte pas la politique de mot de passe'], code: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \App\Exception\DemandeException
     * @throws TransportExceptionInterface
     */
    public function isOldPasswordValid(array $payload, Demandeur $user): void
    {
        $body = [
            'grant_type' => 'password',
            'username' => $user->getEmail(),
            'password' => $payload['password'],
            'client_id' => $this->frontendClientId,
            'scope' => 'openid',
        ];
        $response = $this->httpClient->request(
            'POST',
            $this->idpBasePath.'/realms/'.$this->idpRealm.'/protocol/openid-connect/token',
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => $body,
            ],
        );
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new DemandeException(errors: ['message' => 'l\'ancien mot de passe est incorrect'], code: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \RuntimeException
     */
    public function getMappers(): array
    {
        $mapperResponse = $this->doManagementRequest('GET', '/identity-provider/instances/clavis/mappers');
        if (Response::HTTP_OK !== $mapperResponse->getStatusCode()) {
            throw new \RuntimeException('Une erreur est survenue lors de la récupération des mapper', 500);
        }

        return json_decode(json: $mapperResponse->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws \RuntimeException
     */
    public function updateMapper(array $mapper, string $field, array|string $data, bool $config = true): array
    {
        if (!isset($mapper['id'])) {
            throw new \RuntimeException('Mapper id not found.');
        }
        if (is_array($data) && 'claims' !== $field) {
            throw new \RuntimeException("Incorrect format for field $field data ".print_r($data, true));
        }
        if ($config) {
            if ('claims' === $field) {
                if (isset($mapper['config'][$field])) {
                    $jsonField = json_decode(
                        json: $mapper['config'][$field],
                        associative: true,
                        flags: JSON_THROW_ON_ERROR
                    );
                    $jsonField[] = $data;
                } else {
                    $jsonField = [$data];
                }
                $data = json_encode($jsonField, JSON_THROW_ON_ERROR);
            }
            $mapper['config'][$field] = $data;
        } else {
            $mapper[$field] = $data;
        }

        $response = $this->doManagementRequest(
            method: 'PUT',
            route: '/identity-provider/instances/clavis/mappers/'.$mapper['id'],
            body: $mapper,
        );
        if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
            throw new \RuntimeException('une erreur est survenue lors de la modification du mapper', 500);
        }

        return $mapper;
    }
}
