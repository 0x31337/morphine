<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

use Morphine\Base\Engine\Database\Database;

class LocalProvider implements AuthProviderInterface
{
    private $sessionKey;

    public function __construct(array $config = [])
    {
        $this->sessionKey = $config['session_key'] ?? 'auth_user';
    }

    public function authenticate(array $params)
    {
        $table = $params['table'] ?? ($this->config['user_table'] ?? 'users');
        $username = $params['username'] ?? null;
        $password = $params['password'] ?? null;
        if (!$table || !$username || !$password) {
            return 'MISSING_PARAMS';
        }
        $db = Database::getInstance();
        $user = $db->select('*', $table, ['username' => $username]);
        if (!$user || !isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return 'INVALID_CREDENTIALS';
        }
        session_regenerate_id(true);
        $_SESSION[$this->sessionKey] = $user;
        return $user;
    }

    public function register(array $params)
    {
        $table = $params['table'] ?? ($this->config['user_table'] ?? 'users');
        $username = $params['username'] ?? null;
        $password = $params['password'] ?? null;
        if (!$table || !$username || !$password) {
            return 'MISSING_PARAMS';
        }
        $db = Database::getInstance();
        $existing = $db->select('*', $table, ['username' => $username]);
        if ($existing) {
            return 'USERNAME_EXISTS';
        }
        $data = $params;
        unset($data['table']);
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        unset($data['password']);
        $userId = $db->insert($table, $data);
        if (!$userId) {
            return 'REGISTRATION_FAILED';
        }
        $user = $db->select('*', $table, ['id' => $userId]);
        $_SESSION[$this->sessionKey] = $user;
        return $user;
    }

    public function getUser(): ?array
    {
        // UserModel data fetching is up to the user in their own models/operations
        return null;
    }

    public function logout(): void
    {
        unset($_SESSION[$this->sessionKey]);
    }

    public function getName(): string
    {
        return 'local';
    }
} 