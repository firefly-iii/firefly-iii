<?php
/**
 * AssetAccounts.php
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

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class AssetAccounts.
 */
class AssetAccounts implements MapperInterface
{
    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $set               = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $list              = [];

        /** @var Account $account */
        foreach ($set as $account) {
            $name = $account->name;
            $iban = $account->iban ?? '';
            if (strlen($iban) > 0) {
                $name .= ' (' . $account->iban . ')';
            }
            $list[$account->id] = $name;
        }

        asort($list);

        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;
    }
}
