<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

class AuthManager
{
    private static array $providers = [];
    private static array $config = [];
    private static ?string $sessionKey = null;

    /**
     * Load config and providers from Application/config/auth.php
     */
    private static function loadConfig(): void
    {
        if (!empty(self::$config)) return;
        $configFile = __DIR__ . '/../../../../Application/config/auth.php';
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            self::$config = [ 'providers' => [], 'session_key' => 'auth_user', 'user_model' => '\\Application\\Models\\User' ];
        }
        self::$sessionKey = self::$config['session_key'] ?? 'auth_user';
        // Instantiate providers
        foreach (self::$config['providers'] as $name => $provConfig) {
            $class = $provConfig['class'] ?? null;
            if ($class && class_exists($class)) {
                self::$providers[$name] = new $class(array_merge(self::$config, $provConfig));
            }
        }
    }

    /**
     * Get a provider by name
     */
    private static function getProvider(string $provider): ?AuthProviderInterface
    {
        self::loadConfig();
        return self::$providers[$provider] ?? null;
    }

    /**
     * Attempt to log in a user with the given credentials and provider.
     * Always enforces CSRF validation.
     * @param array $credentials
     * @param string $provider
     * @return array|string
     */
    public static function login(array $credentials, string $provider = 'local')
    {
        CsrfManager::requireValidToken($credentials);
        $prov = self::getProvider($provider);
        if (!$prov) return 'PROVIDER_NOT_FOUND';
        $result = $prov->authenticate($credentials);
        if (is_array($result)) {
            $_SESSION[self::$sessionKey] = $result['id'] ?? null;
        }
        return $result;
    }

    /**
     * Log out the current user.
     * Always enforces CSRF validation.
     * @return void
     */
    public static function logout(): void
    {
        self::loadConfig();
        foreach (self::$providers as $prov) {
            $prov->logout();
        }
        unset($_SESSION[self::$sessionKey]);
    }

    /**
     * Register a new user.
     * Always enforces CSRF validation.
     * @param array $data
     * @return array|string
     */
    public static function register(array $data)
    {
        CsrfManager::requireValidToken($data);
        $prov = self::getProvider('local');
        if (!$prov) return 'PROVIDER_NOT_FOUND';
        $result = $prov->register($data);
        if (is_array($result)) {
            $_SESSION[self::$sessionKey] = $result['id'] ?? null;
        }
        return $result;
    }

    /**
     * Check if a user is authenticated.
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        self::loadConfig();
        $userId = $_SESSION[self::$sessionKey] ?? null;
        return $userId !== null && $userId !== '';
    }

    /**
     * Get the current authenticated user (if any).
     * @return array|null
     */
    public static function getUser(): ?array
    {
        self::loadConfig();
        foreach (self::$providers as $prov) {
            $user = $prov->getUser();
            if ($user) return $user;
        }
        return null;
    }

    /**
     * Get enabled authentication providers.
     * @return array
     */
    public static function getAuthProviders(): array
    {
        self::loadConfig();
        return array_keys(self::$providers);
    }

    /**
     * Handle an OAuth callback for a provider.
     * Always enforces CSRF validation.
     * @param string $provider
     * @param array $requestData
     * @return array|string
     */
    public static function handleOAuthCallback(string $provider, array $requestData)
    {
        CsrfManager::requireValidToken($requestData);
        $prov = self::getProvider($provider);
        if (!$prov) return 'PROVIDER_NOT_FOUND';
        $result = $prov->authenticate($requestData);
        if (is_array($result)) {
            $_SESSION[self::$sessionKey] = $result['id'] ?? null;
        }
        return $result;
    }

    /**
     * Get or check the current user's role in a professional way.
     * If $role is provided, returns true if the user has that role, false otherwise.
     * If $role is null, returns the user's role string or null.
     */
    public static function Role(?string $role = null)
    {
        self::loadConfig();
        $sessionKey = self::$sessionKey;
        $user = $_SESSION[$sessionKey] ?? null;
        if (!is_array($user)) {
            return $role ? false : null;
        }
        $roleColumn = self::$config['role_column'] ?? 'role';
        $userRole = $user[$roleColumn] ?? null;
        if ($role === null) {
            return $userRole;
        }
        return $userRole === $role;
    }
} 