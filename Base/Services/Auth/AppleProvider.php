<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Morphine\Base\Engine\Database\Database;

class AppleProvider implements AuthProviderInterface
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
            'urlAuthorize'            => 'https://appleid.apple.com/auth/authorize',
            'urlAccessToken'          => 'https://appleid.apple.com/auth/token',
            'urlResourceOwnerDetails' => '', // Apple does not provide a userinfo endpoint by default
            'scopes'                  => 'name email',
        ]);
        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
            ]);
            // Apple returns user info only on first login, so you may need to decode from id_token
            $userInfo = [
                'apple_id' => $accessToken->getValues()['sub'] ?? null,
                'email' => $accessToken->getValues()['email'] ?? null,
                'raw' => $accessToken->getValues(),
            ];
            $db = Database::getInstance();
            $user = $db->select('*', $table, ['apple_id' => $userInfo['apple_id']]);
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
        return 'apple';
    }
} 