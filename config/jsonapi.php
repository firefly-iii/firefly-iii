<?php

declare(strict_types=1);

use FireflyIII\JsonApi\V2\Server;

return [
    /*
    |--------------------------------------------------------------------------
    | Root Namespace
    |--------------------------------------------------------------------------
    |
    | The root JSON:API namespace, within your application's namespace.
    | This is used when generating any class that does not sit *within*
    | a server's namespace. For example, new servers and filters.
    |
    | By default this is set to `JsonApi` which means the root namespace
    | will be `\App\JsonApi`, if your application's namespace is `App`.
    */
    'namespace' => 'JsonApi',

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | A list of the JSON:API compliant APIs in your application, referred to
    | as "servers". They must be listed below, with the array key being the
    | unique name for each server, and the value being the fully-qualified
    | class name of the server class.
    */
    'servers'   => [
        'v2' => Server::class,
    ],
];
