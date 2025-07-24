<?php
use Morphine\Base\Services\Auth\AuthManager;

return [
    'guest' => function() { return !AuthManager::isAuthenticated(); },
    'logged_in' => function() { return AuthManager::isAuthenticated(); },
    'superadmin' => function() { return AuthManager::Role('superadmin'); },
    // Add more roles as needed, e.g.:
    // 'admin' => function() { return AuthManager::Role('admin'); },
]; 