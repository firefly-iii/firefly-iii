<?php

/*
 * BudgetLimitObserver.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Models\BudgetLimit;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class BudgetLimitObserver
{
    public function created(BudgetLimit $budgetLimit): void
    {
        Log::debug('Observe "created" of a budget limit.');
        $this->updateNativeAmount($budgetLimit);
    }

    public function updated(BudgetLimit $budgetLimit): void
    {
        Log::debug('Observe "updated" of a budget limit.');
        $this->updateNativeAmount($budgetLimit);
    }

    private function updateNativeAmount(BudgetLimit $budgetLimit): void
    {
        if (!Amount::convertToNative($budgetLimit->budget->user)) {
            return;
        }
        $userCurrency               = app('amount')->getDefaultCurrencyByUserGroup($budgetLimit->budget->user->userGroup);
        $budgetLimit->native_amount = null;
        if ($budgetLimit->transactionCurrency->id !== $userCurrency->id) {
            $converter                  = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $budgetLimit->native_amount = $converter->convert($budgetLimit->transactionCurrency, $userCurrency, today(), $budgetLimit->amount);
        }
        $budgetLimit->saveQuietly();
        Log::debug('Budget limit native amounts are updated.');
    }
}
