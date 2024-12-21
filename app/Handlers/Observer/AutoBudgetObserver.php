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
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class AutoBudgetObserver
{
    public function updated(AutoBudget $autoBudget): void
    {
        Log::debug('Observe "updated" of an auto budget.');
        $this->updateNativeAmount($autoBudget);
    }

    public function created(AutoBudget $autoBudget): void
    {
        Log::debug('Observe "created" of an auto budget.');
        $this->updateNativeAmount($autoBudget);
    }

    private function updateNativeAmount(AutoBudget $autoBudget): void
    {
        $userCurrency              = app('amount')->getDefaultCurrencyByUserGroup($autoBudget->budget->user->userGroup);
        $autoBudget->native_amount = null;
        if ($autoBudget->transactionCurrency->id !== $userCurrency->id) {
            $converter                 = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $autoBudget->native_amount = $converter->convert($autoBudget->transactionCurrency, $userCurrency, today(), $autoBudget->amount);
        }
        $autoBudget->saveQuietly();
        Log::debug('Auto budget native amount is updated.');
    }
}
