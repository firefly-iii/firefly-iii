<?php
declare(strict_types=1);

namespace FireflyIII\Support\Authentication;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Foundation\Application;
use Log;


/**
 * Class RemoteUserGuard
 */
class RemoteUserGuard implements Guard
{
    protected Application $application;
    protected             $provider;
    protected             $user;

    /**
     * Create a new authentication guard.
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     *
     * @return void
     */
    public function __construct(UserProvider $provider, Application $app)
    {
        Log::debug('Constructed RemoteUserGuard');
        $this->application = $app;
        $this->provider    = $provider;
        $this->user        = null;
    }

    /**
     *
     */
    public function authenticate(): void
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        if (!is_null($this->user)) {
            Log::debug('No user found.');

            return;
        }
        // Get the user identifier from $_SERVER
        $userID = request()->server('REMOTE_USER') ?? null;
        if (null === $userID) {
            Log::debug('No user in REMOTE_USER.');
            throw new FireflyException('The REMOTE_USER header was unexpectedly empty.');
        }


        // do some basic debugging here:
        // $userID = 'test@firefly';

        /** @var User $user */
        $user = $this->provider->retrieveById($userID);

        Log::debug(sprintf('Result of getting user from provider: %s', $user->email));
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $result = !is_null($this->user());
        Log::debug(sprintf('Now in check(). Will return %s', var_export($result, true)));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @inheritDoc
     */
    public function id(): ?User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $credentials = [])
    {
        throw new FireflyException('Did not implement RemoteUserGuard::validate()');
    }
}
