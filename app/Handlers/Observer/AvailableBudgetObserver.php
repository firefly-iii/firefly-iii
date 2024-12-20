<?php
/*
 * AutoBudgetObserver.php
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

use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class AvailableBudgetObserver
{
    public function updated(AvailableBudget $availableBudget): void
    {
        Log::debug('Observe "updated" of an available budget.');
        $this->updateNativeAmount($availableBudget);
    }

    public function created(AvailableBudget $availableBudget): void
    {
        Log::debug('Observe "created" of an available budget.');
        $this->updateNativeAmount($availableBudget);
    }

    private function updateNativeAmount(AvailableBudget $availableBudget): void
    {
        $userCurrency = app('amount')->getDefaultCurrencyByUserGroup($availableBudget->user->userGroup);
        $availableBudget->native_amount = null;
        if ($availableBudget->transactionCurrency->id !== $userCurrency->id) {
            $converter = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $availableBudget->native_amount = $converter->convert($availableBudget->transactionCurrency, $userCurrency, today(), $availableBudget->amount);
        }
        $availableBudget->saveQuietly();
        Log::debug('Available budget native amount is updated.');
    }


}
