<?php

namespace FireflyIII\JsonApi\V3;

use FireflyIII\JsonApi\V3\Accounts\AccountSchema;
use FireflyIII\JsonApi\V3\AccountBalances\AccountBalanceSchema;
use FireflyIII\JsonApi\V3\Users\UserSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{

    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/v3';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
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
