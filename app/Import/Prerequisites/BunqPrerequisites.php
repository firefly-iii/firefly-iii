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

use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * This class contains all the routines necessary to connect to Bunq.
 */
class BunqPrerequisites implements PrerequisitesInterface
{
    /** @var User */
    private $user;

    /**
     * Returns view name that allows user to fill in prerequisites. Currently asks for the API key.
     *
     * @return string
     */
    public function getView(): string
    {
        Log::debug('Now in BunqPrerequisites::getView()');

        return 'import.bunq.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        Log::debug('Now in BunqPrerequisites::getViewParameters()');
        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', null);
        $string = '';
        if (!is_null($apiKey)) {
            $string = $apiKey->data;
        }

        return ['key' => $string];
    }

    /**
     * Returns if this import method has any special prerequisites such as config
     * variables or other things. The only thing we verify is the presence of the API key. Everything else
     * tumbles into place: no installation token? Will be requested. No device server? Will be created. Etc.
     *
     * @return bool
     */
    public function hasPrerequisites(): bool
    {
        Log::debug('Now in BunqPrerequisites::hasPrerequisites()');
        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', false);
        $result = (false === $apiKey->data || null === $apiKey->data);

        Log::debug(sprintf('Is apiKey->data false? %s', var_export(false === $apiKey->data, true)));
        Log::debug(sprintf('Is apiKey->data NULL? %s', var_export(null === $apiKey->data, true)));
        Log::debug(sprintf('Result is: %s', var_export($result, true)));

        return $result;
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        Log::debug(sprintf('Now in setUser(#%d)', $user->id));
        $this->user = $user;

        return;
    }

    /**
     * This method responds to the user's submission of an API key. It tries to register this instance as a new Firefly III device.
     * If this fails, the error is returned in a message bag and the user is notified (this is fairly friendly).
     *
     * @param Request $request
     *
     * @return MessageBag
     */
    public function storePrerequisites(Request $request): MessageBag
    {
        $apiKey = $request->get('api_key');
        Log::debug('Storing bunq API key');
        Preferences::setForUser($this->user, 'bunq_api_key', $apiKey);

        return new MessageBag;
    }

}
