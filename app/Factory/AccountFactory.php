<?php
/**
 * AccountFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\User;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Account
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);

        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                return $object;
            }
        }

        return $this->create(
            [
                'user_id'         => $this->user->id,
                'name'            => $accountName,
                'account_type_id' => $type->id,
                'virtual_balance' => '0',
                'iban'            => null,
                'active'          => true,
            ]
        );
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}