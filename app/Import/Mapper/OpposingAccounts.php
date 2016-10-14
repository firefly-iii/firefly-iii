<?php
/**
 * OpposingAccounts.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class OpposingAccounts
 *
 * @package FireflyIII\Import\Mapper
 */
class OpposingAccounts implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $set               = $accountRepository->getAccountsByType(
            [
                AccountType::DEFAULT, AccountType::ASSET,
                AccountType::EXPENSE, AccountType::BENEFICIARY,
                AccountType::REVENUE,
            ]
        );
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

        $list = [0 => trans('csv.do_not_map')] + $list;

        return $list;
    }
}
