<?php

// config for Wijourdil/NtfyNotificationChannel
return [

    'server' => env('NTFY_SERVER', 'https://ntfy.sh'),
    'topic' => env('NTFY_TOPIC', ''),
    'authentication' => [
        'enabled' => (bool) env('NTFY_AUTH_ENABLED', false),
        'username' => env('NTFY_AUTH_USERNAME', ''),
        'password' => env('NTFY_AUTH_PASSWORD', ''),
    ],

];
