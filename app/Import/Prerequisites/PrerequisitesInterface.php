<?php
/**
 * PrerequisitesInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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

/**
 * Interface PrerequisitesInterface
 */
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
     * Indicate if all prerequisites have been met.
     *
     * @return bool
     */
    public function isComplete(): bool;

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * This method responds to the user's submission of an API key. Should do nothing but store the value.
     *
     * Errors must be returned in the message bag under the field name they are requested by.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function storePrerequisites(array $data): MessageBag;
}
