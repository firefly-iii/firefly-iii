<?php

declare(strict_types=1);

namespace FireflyIII\JsonApi\V2;

use FireflyIII\JsonApi\V2\Accounts\AccountSchema;
use FireflyIII\JsonApi\V2\Users\UserSchema;
use FireflyIII\Support\JsonApi\Concerns\UsergroupAware;
use FireflyIII\Support\JsonApi\Concerns\UserGroupDetectable;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Server\Server as BaseServer;

/**
 * Class Server
 *
 * This class serves as a generic class for the v2 API "server".
 */
class Server extends BaseServer
{
    use UsergroupAware;
    use UserGroupDetectable;

    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v2';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        Log::debug(__METHOD__);
        // at this point the user may not actually have access to this user group.
        $res = $this->detectUserGroup();
        $this->setUserGroup($res);
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        Log::debug(__METHOD__);
        return [
            AccountSchema::class,
            UserSchema::class,
            //AccountBalanceSchema::class,
        ];
    }
}
