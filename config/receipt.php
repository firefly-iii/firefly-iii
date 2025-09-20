<?php

return [
    'enabled' => env('RECEIPT_INBOX_ENABLED', false),
    'parser_url' => rtrim(env('RECEIPT_PARSER_URL', 'http://localhost:8000'), '/'),
    'firefly_api_base' => rtrim(env('FIREFLY_SELF_API_BASE', env('APP_URL', 'http://localhost')), '/'),
    'firefly_token' => env('FIREFLY_PERSONAL_TOKEN', ''),
];
