<?php

declare(strict_types=1);

/*
 * TriggersCreditRecalculation.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\Account;

use FireflyIII\Events\Model\Account\CreatedNewAccount;
use FireflyIII\Events\Model\Account\UpdatedExistingAccount;
use FireflyIII\Handlers\ExchangeRate\ConvertsAmountToPrimaryAmount;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class UpdatesAccountInformation
{
    public function handle(CreatedNewAccount|UpdatedExistingAccount $event): void
    {
        $this->recalculateCredit($event->account);
        $this->updateVirtualBalance($event->account);
    }

    private function recalculateCredit(Account $account): void
    {
        Log::debug('Will call CreditRecalculateService because a new account was created.');

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }

    private function updateVirtualBalance(Account $account): void
    {
         $repository   = app(AccountRepositoryInterface::class);
         $currency     = $repository->getAccountCurrency($account);

        if(null !== $currency) {
            // moet dit alleen als het NIET null is?
            ConvertsAmountToPrimaryAmount::convert($account->user, $account, $currency, 'virtual_balance', 'native_virtual_balance');

        }
        return;

        if (!Amount::convertToPrimary($account->user)) {
            Log::debug('After account creation, no need to convert virtual balance.');

            return;
        }
        Log::debug('After account creation, convert virtual balance.');
        $userCurrency = Amount::getPrimaryCurrencyByUserGroup($account->user->userGroup);
        $repository   = app(AccountRepositoryInterface::class);
        $currency     = $repository->getAccountCurrency($account);
        if (
            null !== $currency
            && $currency->id !== $userCurrency->id
            && '' !== (string) $account->virtual_balance
            && 0 !== bccomp($account->virtual_balance, '0')
        ) {
            $converter                       = new ExchangeRateConverter();
            $converter->setUserGroup($account->user->userGroup);
            $converter->setIgnoreSettings(true);
            $account->native_virtual_balance = $converter->convert($currency, $userCurrency, today(), $account->virtual_balance);
        }
        if ('' === (string) $account->virtual_balance || 0 === bccomp($account->virtual_balance, '0')) {
            $account->virtual_balance        = null;
            $account->native_virtual_balance = null;
        }
        $account->saveQuietly();

        // Log::debug('Account primary currency virtual balance is updated.');
    }
}
