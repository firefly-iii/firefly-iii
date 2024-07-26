<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2;

use FireflyIII\JsonApi\V2\Accounts\AccountSchema;
use FireflyIII\JsonApi\V2\AccountBalances\AccountBalanceSchema;
use FireflyIII\JsonApi\V2\Users\UserSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v3';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            AccountSchema::class,
            UserSchema::class,
            AccountBalanceSchema::class,
        ];
    }
}
