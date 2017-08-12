<?php
/**
 * PrerequisitesInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Prerequisites;


use FireflyIII\User;

interface PrerequisitesInterface
{
    /**
     * Returns view name that allows user to fill in prerequisites.
     *
     * @return string
     */
    public function getView(): string;

    /**
     * Returns any values required for the prerequisites-view.
     *
     * @return array
     */
    public function getViewParameters(): array;

    /**
     * Returns if this import method has any special prerequisites such as config
     * variables or other things.
     *
     * @return bool
     */
    public function hasPrerequisites(): bool;

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void;
}