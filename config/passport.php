<?php

declare(strict_types=1);

return [

    /*
|--------------------------------------------------------------------------
| Passport Guard
|--------------------------------------------------------------------------
|
| Here you may specify which authentication guard Passport will use when
| authenticating users. This value should correspond with one of your
| guards that is already present in your "auth" configuration file.
|
*/

    'guard'                  => env_default_when_empty(env('AUTHENTICATION_GUARD'), 'web'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key'            => env('PASSPORT_PRIVATE_KEY'),

    'public_key'             => env('PASSPORT_PUBLIC_KEY'),

    'personal_access_client' => [
        'id'     => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    'middleware'             => [],
    'connection'             => env('PASSPORT_CONNECTION'),

];
