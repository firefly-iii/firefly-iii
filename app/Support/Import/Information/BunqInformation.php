<?php
/**
 * BunqInformation.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Information;

use FireflyIII\Services\Bunq\Request\DeleteDeviceSessionRequest;
use FireflyIII\Services\Bunq\Request\DeviceSessionRequest;
use FireflyIII\Services\Bunq\Request\ListUserRequest;
use FireflyIII\Services\Bunq\Token\SessionToken;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class BunqInformation
 *
 * @package FireflyIII\Support\Import\Information
 */
class BunqInformation implements InformationInterface
{

    /** @var  User */
    private $user;

    /**
     * Returns a collection of accounts. Preferrably, these follow a uniform Firefly III format so they can be managed over banks.
     *
     * @return Collection
     */
    public function getAccounts(): Collection
    {
        Log::debug('Now in getAccounts()');
        $sessionToken = $this->startSession();
        $this->getUserInformation($sessionToken);

        // get list of Bunq accounts:


        $this->closeSession($sessionToken);

        return new Collection;
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param SessionToken $sessionToken
     */
    private function closeSession(SessionToken $sessionToken): void
    {
        Log::debug('Going to close session');
        $apiKey          = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $server          = config('firefly.bunq.server');
        $privateKey      = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $request         = new DeleteDeviceSessionRequest();
        $request->setSecret($apiKey);
        $request->setServer($server);
        $request->setPrivateKey($privateKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setSessionToken($sessionToken);
        $request->call();
        return;
    }

    /**
     * @param SessionToken $sessionToken
     */
    private function getUserInformation(SessionToken $sessionToken): void
    {
        $apiKey            = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey   = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $server            = config('firefly.bunq.server');
        $privateKey        = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $request = new ListUserRequest;
        $request->setSessionToken($sessionToken);
        $request->setSecret($apiKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setServer($server);
        $request->setPrivateKey($privateKey);
        $request->call();
        // return the first that isn't null?
        // get all objects, try to find ID.
        var_dump($request->getUserCompany());
        var_dump($request->getUserLight());
        var_dump($request->getUserPerson());

        return;
    }

    /**
     * @return SessionToken
     */
    private function startSession(): SessionToken
    {
        Log::debug('Now in startSession.');
        $apiKey            = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey   = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $server            = config('firefly.bunq.server');
        $privateKey        = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $installationToken = Preferences::getForUser($this->user, 'bunq_installation_token')->data;
        $request           = new DeviceSessionRequest();
        $request->setSecret($apiKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setServer($server);
        $request->setPrivateKey($privateKey);
        $request->setInstallationToken($installationToken);
        $request->call();
        $sessionToken = $request->getSessionToken();
        Log::debug(sprintf('Now have got session token: %s', serialize($sessionToken)));

        return $sessionToken;
    }
}