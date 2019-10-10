<?php
/**
 * YnabPrerequisites.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Prerequisites;

use FireflyIII\User;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class YnabPrerequisites
 */
class YnabPrerequisites implements PrerequisitesInterface
{
    /** @var User The current user */
    private $user;

    /**
     * YnabPrerequisites constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Returns view name that allows user to fill in prerequisites.
     *
     * @return string
     */
    public function getView(): string
    {
        return 'import.ynab.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        Log::debug('Now in YnabPrerequisites::getViewParameters()');
        $clientId     = '';
        $clientSecret = '';
        if ($this->hasClientId()) {
            $clientId = app('preferences')->getForUser($this->user, 'ynab_client_id', null)->data;
        }
        if ($this->hasClientSecret()) {
            $clientSecret = app('preferences')->getForUser($this->user, 'ynab_client_secret', null)->data;
        }

        $callBackUri = route('import.callback.ynab');
        $isHttps     = 0 === strpos($callBackUri, 'https://');

        return ['client_id' => $clientId, 'client_secret' => $clientSecret, 'callback_uri' => $callBackUri, 'is_https' => $isHttps];
    }

    /**
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->hasClientId() && $this->hasClientSecret();
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
        $clientId     = $data['client_id'] ?? '';
        $clientSecret = $data['client_secret'] ?? '';
        Log::debug('Storing YNAB client data');
        app('preferences')->setForUser($this->user, 'ynab_client_id', $clientId);
        app('preferences')->setForUser($this->user, 'ynab_client_secret', $clientSecret);

        return new MessageBag;
    }

    /**
     * Check if we have the client ID.
     *
     * @return bool
     */
    private function hasClientId(): bool
    {
        $clientId = app('preferences')->getForUser($this->user, 'ynab_client_id', null);
        if (null === $clientId) {
            return false;
        }
        if ('' === (string)$clientId->data) {
            return false;
        }

        return true;
    }

    /**
     * Check if we have the client secret
     *
     * @return bool
     */
    private function hasClientSecret(): bool
    {
        $clientSecret = app('preferences')->getForUser($this->user, 'ynab_client_secret', null);
        if (null === $clientSecret) {
            return false;
        }
        if ('' === (string)$clientSecret->data) {
            return false;
        }

        return true;
    }
}
