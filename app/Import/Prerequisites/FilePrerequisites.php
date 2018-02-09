<?php
/**
 * FilePrerequisites.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

/**
 * This class contains all the routines necessary to import from a file. Hint: there are none.
 */
class FilePrerequisites implements PrerequisitesInterface
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
        return '';
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
     * variables or other things. The only thing we verify is the presence of the API key. Everything else
     * tumbles into place: no installation token? Will be requested. No device server? Will be created. Etc.
     *
     * True if prerequisites. False if not.
     *
     * @return bool
     * @throws FireflyException
     */
    public function hasPrerequisites(): bool
    {
        if ($this->user->hasRole('demo')) {
            throw new FireflyException('Apologies, the demo user cannot import files.');
        }

        return false;
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
        return new MessageBag;
    }
}
