<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Morphine\Base\Engine\Database\Database;

class GoogleProvider implements AuthProviderInterface
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
        if (!class_exists(Google::class)) {
            return 'OAUTH_LIBRARY_NOT_INSTALLED';
        }
        $provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => $this->redirectUri,
        ]);
        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
            ]);
            $googleUser = $provider->getResourceOwner($accessToken);
            $userInfo = [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'raw' => $googleUser->toArray(),
            ];
            $db = Database::getInstance();
            // Try to find user by google_id
            $user = $db->select('*', $table, ['google_id' => $userInfo['google_id']]);
            if (!$user) {
                // Create user with all available info
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
        return 'google';
    }
} 