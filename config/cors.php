<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // NOTE: dohsox.com lapsed and is now registered by a third party - never re-add it
    // here; supports_credentials is true, so cookies would be sent to whoever owns it.
    'allowed_origins' => ['https://telemed.doh12.com', 'http://localhost:5173', 'http://127.0.0.1:5173'],

    //LOCAL TEST CED
    // 'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://10.10.124.140:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
