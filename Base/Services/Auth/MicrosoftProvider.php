<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Morphine\Base\Engine\Database\Database;

class MicrosoftProvider implements AuthProviderInterface
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $sessionKey;
    private $config;

    public function __construct(array $config = [])
    {
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
        $this->sessionKey = $config['session_key'] ?? 'auth_user';
        $this->config = $config;
    }

    public function authenticate(array $params)
    {
        $table = $params['table'] ?? null;
        if (!$table) {
            return 'MISSING_PARAMS';
        }
        if (empty($params['code'])) {
            return 'OAUTH_CODE_MISSING';
        }
        if (!class_exists(GenericProvider::class)) {
            return 'OAUTH_LIBRARY_NOT_INSTALLED';
        }
        $provider = new GenericProvider([
            'clientId'                => $this->clientId,
            'clientSecret'            => $this->clientSecret,
            'redirectUri'             => $this->redirectUri,
            'urlAuthorize'            => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'urlAccessToken'          => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
            'scopes'                  => 'openid profile email',
        ]);
        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
            ]);
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $userInfo = [
                'microsoft_id' => $resourceOwner->getId(),
                'email' => $resourceOwner->toArray()['mail'] ?? $resourceOwner->toArray()['userPrincipalName'] ?? null,
                'name' => $resourceOwner->toArray()['displayName'] ?? null,
                'raw' => $resourceOwner->toArray(),
            ];
            $db = Database::getInstance();
            $user = $db->select('*', $table, ['microsoft_id' => $userInfo['microsoft_id']]);
            if (!$user) {
                $insertData = array_merge($params, $userInfo);
                unset($insertData['table'], $insertData['code']);
                $userId = $db->insert($table, $insertData);
                if (!$userId) {
                    return 'OAUTH_USER_CREATE_FAILED';
                }
                $user = $db->select('*', $table, ['id' => $userId]);
            }
            session_regenerate_id(true);
            $_SESSION[$this->sessionKey] = $user['id'];
            return $user;
        } catch (IdentityProviderException $e) {
            return 'OAUTH_IDENTITY_ERROR:' . $e->getMessage();
        } catch (\Exception $e) {
            return 'OAUTH_ERROR:' . $e->getMessage();
        }
    }

    public function register(array $params)
    {
        return 'OAUTH_REGISTER_NOT_SUPPORTED';
    }

    public function getUser(): ?array
    {
        return null;
    }

    public function logout(): void
    {
        unset($_SESSION[$this->sessionKey]);
    }

    public function getName(): string
    {
        return 'microsoft';
    }
} 