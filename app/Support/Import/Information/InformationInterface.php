<?php
/**
 * InformationInterface.php
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

namespace FireflyIII\Support\Import\Information;

use FireflyIII\User;

/**
 * Interface InformationInterface
 *
 * @package FireflyIII\Support\Import\Information
 */
interface InformationInterface
{

    /**
     * Returns a collection of accounts. Preferrably, these follow a uniform Firefly III format so they can be managed over banks.
     *
     * The format for these bank accounts is basically this:
     *
     * id: bank specific id
     * name: bank appointed name
     * number: account number (usually IBAN)
     * currency: ISO code of currency
     *
     * any other fields are optional but can be useful:
     * image: logo or account specific thing
     *
     * @return array
     */
    public function getAccounts(): array;

    /**
     * Set the user for this Prerequisites-routine. Class is expected to implement and save this.
     *
     * @param User $user
     */
    public function setUser(User $user): void;
}
