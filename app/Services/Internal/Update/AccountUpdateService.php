<?php
/**
 * AccountUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\Account;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;

/**
 * Class AccountUpdateService
 */
class AccountUpdateService
{
    use AccountServiceTrait;


    /**
     * Update account data.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        // update the account itself:
        $account->name            = $data['name'];
        $account->active          = $data['active'];
        $account->virtual_balance = trim($data['virtualBalance']) === '' ? '0' : $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        // update all meta data:
        $this->updateMetaData($account, $data);

        // has valid initial balance (IB) data?
        if ($this->validIBData($data)) {
            // then do update!
            $this->updateIB($account, $data);
        }

        // if not, delete it when exist.
        if (!$this->validIBData($data)) {
            $this->deleteIB($account);
        }

        // update note:
        if (isset($data['notes']) && null !== $data['notes']) {
            $this->updateNote($account, (string)$data['notes']);
        }

        return $account;
    }
}
