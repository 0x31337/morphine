<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

class CsrfManager
{
    /**
     * Get or generate the CSRF token for the current session.
     * @return string
     */
    public static function getToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token.
     * @param string $token
     * @return bool
     */
    public static function validateToken(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Require a valid CSRF token in $reqData['csrf_token'] (exits on failure).
     * @param array $reqData
     * @return void
     */
    public static function requireValidToken(array $reqData): void
    {
        if (empty($reqData['csrf_token']) || !self::validateToken($reqData['csrf_token'])) {
            http_response_code(403);
            exit('CSRF_TOKEN_INVALID');
        }
    }
} 