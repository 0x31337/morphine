<?php
// Auth configuration for Morphine
//
// To enable role-based access control, specify the table and column responsible for user roles.
// Example:
//   'user_table' => 'users',
//   'role_column' => 'role',
//
// If your users table or role column has a different name, set it here.
// If you do not use roles, you can omit 'role_column'.
return [
    'providers' => [
        'local' => [
            'class' => '\Morphine\Base\Services\Auth\LocalProvider',
        ],
        'google' => [
            'class' => '\Morphine\Base\Services\Auth\GoogleProvider',
            'client_id' => 'GOOGLE_CLIENT_ID',
            'client_secret' => 'GOOGLE_CLIENT_SECRET',
            'redirect_uri' => 'https://yourapp.com/auth/oauth_callback?provider=google',
        ],
        'microsoft' => [
            'class' => '\Morphine\Base\Services\Auth\MicrosoftProvider',
            'client_id' => 'MICROSOFT_CLIENT_ID',
            'client_secret' => 'MICROSOFT_CLIENT_SECRET',
            'redirect_uri' => 'https://yourapp.com/auth/oauth_callback?provider=microsoft',
        ],
        'github' => [
            'class' => '\Morphine\Base\Services\Auth\GithubProvider',
            'client_id' => 'GITHUB_CLIENT_ID',
            'client_secret' => 'GITHUB_CLIENT_SECRET',
            'redirect_uri' => 'https://yourapp.com/auth/oauth_callback?provider=github',
        ],
        'apple' => [
            'class' => '\Morphine\Base\Services\Auth\AppleProvider',
            'client_id' => 'APPLE_CLIENT_ID',
            'client_secret' => 'APPLE_CLIENT_SECRET',
            'redirect_uri' => 'https://yourapp.com/auth/oauth_callback?provider=apple',
        ],
    ],
    'session_key' => 'auth_user',
    // Set your users table and role column here:
    'user_table' => 'users', // Name of your users table
    'role_column' => 'role', // Name of the column that stores the user's role (optional)
]; 