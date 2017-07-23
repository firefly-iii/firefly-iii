<?php
/**
 * AssetAccountIbans.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class AssetAccounts
 *
 * @package FireflyIII\Import\Mapper
 */
class AssetAccountIbans implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $set               = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $topList           = [];
        $list              = [];

        /** @var Account $account */
        foreach ($set as $account) {
            $iban = $account->iban ?? '';
            if (strlen($iban) > 0) {
                $topList[$account->id] = $account->iban . ' (' . $account->name . ')';
            }
            if (strlen($iban) === 0) {
                $list[$account->id] = $account->name;
            }
        }
        asort($topList);
        asort($list);

        $list = $topList + $list;
        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;

    }
}
