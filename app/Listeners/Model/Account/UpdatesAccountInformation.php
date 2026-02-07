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
use FireflyIII\Handlers\ExchangeRate\ConversionParameters;
use FireflyIII\Handlers\ExchangeRate\ConvertsAmountToPrimaryAmount;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdatesAccountInformation implements ShouldQueue
{
    public function handle(CreatedNewAccount|UpdatedExistingAccount $event): void
    {
        $this->recalculateCredit($event->account);
        $this->updateVirtualBalance($event->account);
    }

    private function recalculateCredit(Account $account): void
    {
        Log::debug('Will call CreditRecalculateService because a new account was created or updated.');

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }

    private function updateVirtualBalance(Account $account): void
    {
        Log::debug('Will updateVirtualBalance');
        $repository = app(AccountRepositoryInterface::class);
        $currency   = $repository->getAccountCurrency($account);

        if (null !== $currency) {
            // only when the account has a currency, because that is the only way for the
            // account to have a virtual balance.
            $params                     = new ConversionParameters();
            $params->user               = $account->user;
            $params->model              = $account;
            $params->originalCurrency   = $currency;
            $params->amountField        = 'virtual_balance';
            $params->primaryAmountField = 'native_virtual_balance';
            ConvertsAmountToPrimaryAmount::convert($params);
            Log::debug('Account primary currency virtual balance is updated.');
        }
    }
}
