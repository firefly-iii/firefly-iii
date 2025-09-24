<?php
return [
    'enabled' => env('RECEIPT_INBOX_ENABLED', false),
    'firefly_api_base' => env('FIREFLY_API_BASE', 'http://127.0.0.1:8080'),
    'firefly_token'    => env('FIREFLY_PERSONAL_TOKEN', ''),
    'parser_url'       => env('RECEIPT_PARSER_URL', ''),
];
