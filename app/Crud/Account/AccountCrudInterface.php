<?php
/**
 * AccountCrudInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Crud\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use Illuminate\Support\Collection;

/**
 * Interface AccountCrudInterface
 *
 * @package FireflyIII\Crud\Account
 */
interface AccountCrudInterface
{

    /**
     * WILL BE REMOVED.
     *
     * @param string $name
     * @param array  $types
     *
     * @return Account
     */
    public function findByName(string $name, array $types): Account;

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data) : Account;

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account;

}
