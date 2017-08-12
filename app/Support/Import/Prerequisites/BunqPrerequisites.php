<?php
/**
 * BunqPrerequisites.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Prerequisites;

use FireflyIII\User;
use Preferences;

/**
 * Class BunqPrerequisites
 *
 * @package FireflyIII\Support\Import\Prerequisites
 */
class BunqPrerequisites implements PrerequisitesInterface
{
    /** @var  User */
    private $user;

    /**
     * Returns view name that allows user to fill in prerequisites.
     *
     * @return string
     */
    public function getView(): string
    {
        return 'import.bunq.prerequisites';
    }

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array
    {
        return [];
    }

    /**
     * Returns if this import method has any special prerequisites such as config
     * variables or other things.
     *
     * @return bool
     */
    public function hasPrerequisites(): bool
    {
        $apiKey = Preferences::getForUser($this->user, 'bunq_api_key', false);

        return ($apiKey->data === false);
    }

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;

        return;
    }
}
