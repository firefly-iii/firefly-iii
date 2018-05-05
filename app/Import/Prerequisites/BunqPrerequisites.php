<?php
/**
 * BunqPrerequisites.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Prerequisites;

use FireflyIII\Services\IP\IPRetrievalInterface;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * @deprecated
 * @codeCoverageIgnore
 * This class contains all the routines necessary to connect to Bunq.
 */
class BunqPrerequisites implements PrerequisitesInterface
{
//    /** @var User */
//    private $user;
//
//    /**
//     * Returns view name that allows user to fill in prerequisites. Currently asks for the API key.
//     *
//     * @return string
//     */
//    public function getView(): string
//    {
//        Log::debug('Now in BunqPrerequisites::getView()');
//
//        return 'import.bunq.prerequisites';
//    }
//
//    /**
//     * Returns any values required for the prerequisites-view.
//     *
//     * @return array
//     */
//    public function getViewParameters(): array
//    {
//        Log::debug('Now in BunqPrerequisites::getViewParameters()');
//        $key      = '';
//        $serverIP = '';
//        if ($this->hasApiKey()) {
//            $key = Preferences::getForUser($this->user, 'bunq_api_key', null)->data;
//        }
//        if ($this->hasServerIP()) {
//            $serverIP = Preferences::getForUser($this->user, 'external_ip', null)->data;
//        }
//        if (!$this->hasServerIP()) {
//            /** @var IPRetrievalInterface $service */
//            $service  = app(IPRetrievalInterface::class);
//            $serverIP = (string)$service->getIP();
//        }
//
//
//        // get IP address
//        return ['key' => $key, 'ip' => $serverIP];
//    }
//
//    /**
//     * Returns if this import method has any special prerequisites such as config
//     * variables or other things. The only thing we verify is the presence of the API key. Everything else
//     * tumbles into place: no installation token? Will be requested. No device server? Will be created. Etc.
//     *
//     * @return bool
//     */
//    public function hasPrerequisites(): bool
//    {
//        $hasApiKey   = $this->hasApiKey();
//        $hasServerIP = $this->hasServerIP();
//
//        return !$hasApiKey || !$hasServerIP;
//    }
//
//    /**
//     * Indicate if all prerequisites have been met.
//     *
//     * @return bool
//     */
//    public function isComplete(): bool
//    {
//        // is complete when user has entered both the API key
//        // and his IP address.
//
//        $hasApiKey   = $this->hasApiKey();
//        $hasServerIP = $this->hasServerIP();
//
//        return $hasApiKey && $hasServerIP;
//    }
//
//    /**
//     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
//     *
//     * @param User $user
//     */
//    public function setUser(User $user): void
//    {
//        Log::debug(sprintf('Now in setUser(#%d)', $user->id));
//        $this->user = $user;
//    }
//
//    /**
//     * This method responds to the user's submission of an API key. It tries to register this instance as a new Firefly III device.
//     * If this fails, the error is returned in a message bag and the user is notified (this is fairly friendly).
//     *
//     * @param Request $request
//     *
//     * @return MessageBag
//     */
//    public function storePrerequisites(Request $request): MessageBag
//    {
//        $apiKey   = $request->get('api_key');
//        $serverIP = $request->get('external_ip');
//        Log::debug('Storing bunq API key');
//        Preferences::setForUser($this->user, 'bunq_api_key', $apiKey);
//        Preferences::setForUser($this->user, 'external_ip', $serverIP);
//
//        return new MessageBag;
//    }
//
//    /**
//     * @return bool
//     */
//    private function hasApiKey(): bool
//    {
//        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', false);
//        if (null === $apiKey) {
//            return false;
//        }
//        if (null === $apiKey->data) {
//            return false;
//        }
//        if (\strlen((string)$apiKey->data) === 64) {
//            return true;
//        }
//
//        return false;
//    }
//
//    /**
//     * @return bool
//     */
//    private function hasServerIP(): bool
//    {
//        $serverIP = Preferences::getForUser($this->user, 'external_ip', false);
//        if (null === $serverIP) {
//            return false;
//        }
//        if (null === $serverIP->data) {
//            return false;
//        }
//        if (\strlen((string)$serverIP->data) > 6) {
//            return true;
//        }
//
//        return false;
//    }
    /**
     * Returns view name that allows user to fill in prerequisites.
     *
     * @return string
     */
    public function getView(): string
    {
        // TODO: Implement getView() method.
        throw new NotImplementedException;
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        // TODO: Implement getViewParameters() method.
        throw new NotImplementedException;
    }

    /**
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        // TODO: Implement isComplete() method.
        throw new NotImplementedException;
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        // TODO: Implement setUser() method.
        throw new NotImplementedException;
    }

    /**
     * This method responds to the user's submission of an API key. Should do nothing but store the value.
     *
     * Errors must be returned in the message bag under the field name they are requested by.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function storePrerequisites(array $data): MessageBag
    {
        // TODO: Implement storePrerequisites() method.
        throw new NotImplementedException;
    }
}
