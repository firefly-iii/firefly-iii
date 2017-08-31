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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Bunq\Object\Alias;
use FireflyIII\Services\Bunq\Object\MonetaryAccountBank;
use FireflyIII\Services\Bunq\Request\DeleteDeviceSessionRequest;
use FireflyIII\Services\Bunq\Request\DeviceSessionRequest;
use FireflyIII\Services\Bunq\Request\ListMonetaryAccountRequest;
use FireflyIII\Services\Bunq\Request\ListUserRequest;
use FireflyIII\Services\Bunq\Token\SessionToken;
use FireflyIII\Support\CacheProperties;
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
     * The format for these bank accounts is basically this:
     *
     * id: bank specific id
     * name: bank appointed name
     * number: account number (usually IBAN)
     * currency: ISO code of currency
     * balance: current balance
     *
     *
     * any other fields are optional but can be useful:
     * image: logo or account specific thing
     * color: any associated color.
     *
     * @return array
     */
    public function getAccounts(): array
    {
        // cache for an hour:
        $cache = new CacheProperties;
        $cache->addProperty('bunq.get-accounts');
        $cache->addProperty(date('dmy h'));
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        Log::debug('Now in getAccounts()');
        $sessionToken = $this->startSession();
        $id           = $this->getUserInformation($sessionToken);
        // get list of Bunq accounts:
        $accounts = $this->getMonetaryAccounts($sessionToken, $id);
        $return   = [];
        /** @var MonetaryAccountBank $account */
        foreach ($accounts as $account) {

            $current = [
                'id'       => $account->getId(),
                'name'     => $account->getDescription(),
                'currency' => $account->getCurrency(),
                'balance'  => $account->getBalance()->getValue(),
                'color'    => $account->getSetting()->getColor(),
            ];
            /** @var Alias $alias */
            foreach ($account->getAliases() as $alias) {
                if ($alias->getType() === 'IBAN') {
                    $current['number'] = $alias->getValue();
                }
            }
            $return[] = $current;
        }
        $cache->store($return);

        $this->closeSession($sessionToken);

        return $return;
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
        $privateKey      = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $request         = new DeleteDeviceSessionRequest();
        $request->setSecret($apiKey);
        $request->setPrivateKey($privateKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setSessionToken($sessionToken);
        $request->call();

        return;
    }

    /**
     * @param SessionToken $sessionToken
     * @param int          $userId
     *
     * @return Collection
     */
    private function getMonetaryAccounts(SessionToken $sessionToken, int $userId): Collection
    {
        $apiKey          = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $privateKey      = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $request         = new ListMonetaryAccountRequest;

        $request->setSessionToken($sessionToken);
        $request->setSecret($apiKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setPrivateKey($privateKey);
        $request->setUserId($userId);
        $request->call();

        return $request->getMonetaryAccounts();

    }

    /**
     * @param SessionToken $sessionToken
     *
     * @return int
     * @throws FireflyException
     */
    private function getUserInformation(SessionToken $sessionToken): int
    {
        $apiKey          = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $privateKey      = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $request         = new ListUserRequest;
        $request->setSessionToken($sessionToken);
        $request->setSecret($apiKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setPrivateKey($privateKey);
        $request->call();
        // return the first that isn't null?
        $company = $request->getUserCompany();
        if ($company->getId() > 0) {
            return $company->getId();
        }
        $user = $request->getUserPerson();
        if ($user->getId() > 0) {
            return $user->getId();
        }
        throw new FireflyException('Expected user or company from Bunq, but got neither.');
    }

    /**
     * @return SessionToken
     */
    private function startSession(): SessionToken
    {
        Log::debug('Now in startSession.');
        $apiKey            = Preferences::getForUser($this->user, 'bunq_api_key')->data;
        $serverPublicKey   = Preferences::getForUser($this->user, 'bunq_server_public_key')->data;
        $privateKey        = Preferences::getForUser($this->user, 'bunq_private_key')->data;
        $installationToken = Preferences::getForUser($this->user, 'bunq_installation_token')->data;
        $request           = new DeviceSessionRequest();
        $request->setSecret($apiKey);
        $request->setServerPublicKey($serverPublicKey);
        $request->setPrivateKey($privateKey);
        $request->setInstallationToken($installationToken);
        $request->call();
        $sessionToken = $request->getSessionToken();
        Log::debug(sprintf('Now have got session token: %s', serialize($sessionToken)));

        return $sessionToken;
    }
}
