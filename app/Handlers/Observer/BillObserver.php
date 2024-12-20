<?php

/*
 * BillObserver.php
 * Copyright (c) 2023 james@firefly-iii.org
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
declare(strict_types=1);

namespace FireflyIII\Handlers\Observer;

use FireflyIII\Models\Bill;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

/**
 * Class BillObserver
 */
class BillObserver
{
    public function deleting(Bill $bill): void
    {
        app('log')->debug('Observe "deleting" of a bill.');
        foreach ($bill->attachments()->get() as $attachment) {
            $attachment->delete();
        }
        $bill->notes()->delete();
    }

    public function updated(Bill $bill): void
    {
        Log::debug('Observe "updated" of a bill.');
        $this->updateNativeAmount($bill);
    }

    public function created(Bill $bill): void
    {
        Log::debug('Observe "created" of a bill.');
        $this->updateNativeAmount($bill);
    }

    private function updateNativeAmount(Bill $bill): void
    {
        $userCurrency = app('amount')->getDefaultCurrencyByUserGroup($bill->user->userGroup);
        $bill->native_amount_min = null;
        $bill->native_amount_max = null;
        if ($bill->transactionCurrency->id !== $userCurrency->id) {
            $converter = new ExchangeRateConverter();
            $converter->setIgnoreSettings(true);
            $bill->native_amount_min = $converter->convert($bill->transactionCurrency, $userCurrency, today(), $bill->amount_min);
            $bill->native_amount_max = $converter->convert($bill->transactionCurrency, $userCurrency, today(), $bill->amount_max);
        }
        $bill->saveQuietly();
        Log::debug('Bill native amounts are updated.');
    }
}
