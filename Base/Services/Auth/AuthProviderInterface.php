<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Auth;

interface AuthProviderInterface
{
    /**
     * Attempt to authenticate a user with the given credentials.
     * @param array $credentials
     * @return array|string UserModel data array on success, or error code string
     */
    public function authenticate(array $credentials);

    /**
     * Register a new user (if supported by provider).
     * @param array $data
     * @return array|string UserModel data array on success, or error code string
     */
    public function register(array $data);

    /**
     * Get the current authenticated user (if any).
     * @return array|null
     */
    public function getUser(): ?array;

    /**
     * Log out the current user (if supported).
     * @return void
     */
    public function logout(): void;

    /**
     * Get the provider name (e.g., 'local', 'google').
     * @return string
     */
    public function getName(): string;
} 