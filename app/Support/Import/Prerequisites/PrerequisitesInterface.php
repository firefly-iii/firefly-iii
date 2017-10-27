<?php
/**
 * PrerequisitesInterface.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Prerequisites;


use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

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

    /**
     * @param Request $request
     *
     * @return MessageBag
     */
    public function storePrerequisites(Request $request): MessageBag;
}
