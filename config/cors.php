<?php

return [


    'paths' => ['*', 'sanctum/csrf-cookie', 'api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000'), 'http://localhost:8100', 'capacitor://localhost', 'http://127.0.0.1:8100'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
