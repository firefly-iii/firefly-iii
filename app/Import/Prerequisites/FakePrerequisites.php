<?php
/**
 * FakePrerequisites.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

/**
 * This class contains all the routines necessary for the fake import provider.
 *
 * Class FakePrerequisites
 */
class FakePrerequisites implements PrerequisitesInterface
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
        return 'import.fake.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        $apiKey = '';
        if ($this->hasApiKey()) {
            $apiKey = app('preferences')->getForUser($this->user, 'fake_api_key', null)->data;
        }
        $oldKey = (string)\request()->old('api_key');
        if ($oldKey !== '') {
            $apiKey = \request()->old('api_key');
        }

        return ['api_key' => $apiKey];
    }

    /**
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->hasApiKey();
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
     * @param Request $request
     *
     * @return MessageBag
     */
    public function storePrerequisites(Request $request): MessageBag
    {
        $apiKey     = (string)$request->get('api_key');
        $messageBag = new MessageBag();
        if (32 !== \strlen($apiKey)) {
            $messageBag->add('api_key', 'API key must be 32 chars.');

            return $messageBag;
        }

        app('preferences')->setForUser($this->user, 'fake_api_key', $apiKey);

        return $messageBag;
    }

    /**
     * @return bool
     */
    private function hasApiKey(): bool
    {
        $apiKey = app('preferences')->getForUser($this->user, 'fake_api_key', false);
        if (null === $apiKey) {
            return false;
        }
        if (null === $apiKey->data) {
            return false;
        }
        if (\strlen((string)$apiKey->data) === 32) {
            return true;
        }

        return false;
    }
}
