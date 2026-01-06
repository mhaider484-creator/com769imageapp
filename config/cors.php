<?php

return [

    'paths' => [
        'api/*',
        'login',
        'logout',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://127.0.0.1:8003',
        'https://photoappfrontend.z36.web.core.windows.net',
        'https://photosharefrontend.z38.web.core.windows.net'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

