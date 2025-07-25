<?php
// Application asset configuration
// Define global and per-view (conditional) assets by key. Only configure what you need.

return [
    // Global assets loaded for every view (except 'ajax')
    'global' => [
        'css' => ['bootstrap', 'globals'],
        'js'  => ['jquery', 'globals', 'ajax'],
    ],
    // Asset file definitions (key => path)
    'css' => [
        'bootstrap'   => 'css/bootstrap.min.css',
        'globals'     => 'css/globals.css',
        'guestframe'  => 'css/guestframe.css',
    ],
    'js' => [
        'jquery'   => 'js/jquery-3.6.0.min.js',
        'globals'  => 'js/globals.js',
        'ajax'     => 'js/ajax.js',
    ],
    'img' => [
        'logo'   => 'img/logo.png',
        'header' => 'img/header.png',
    ],
    // Per-view (conditional) assets
    'views' => [
        'guestframe' => [
            'css' => ['guestframe'],
        ],
        // Add more views as needed
    ],
]; 