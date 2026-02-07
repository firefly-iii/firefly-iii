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

use FireflyIII\Handlers\ExchangeRate\ConversionParameters;
use FireflyIII\Handlers\ExchangeRate\ConvertsAmountToPrimaryAmount;
use FireflyIII\Models\Bill;

/**
 * Class BillObserver
 */
class BillObserver
{
    public function created(Bill $bill): void
    {
        $this->updatePrimaryCurrencyAmount($bill);
    }

    public function updated(Bill $bill): void
    {
        //        Log::debug('Observe "updated" of a bill.');
        $this->updatePrimaryCurrencyAmount($bill);
    }

    private function updatePrimaryCurrencyAmount(Bill $bill): void
    {
        $params                     = new ConversionParameters();
        $params->user               = $bill->user;
        $params->model              = $bill;
        $params->originalCurrency   = $bill->transactionCurrency;
        $params->amountField        = 'amount_min';
        $params->primaryAmountField = 'native_amount_min';
        ConvertsAmountToPrimaryAmount::convert($params);

        // and again!
        $params->amountField        = 'amount_max';
        $params->primaryAmountField = 'native_amount_max';
        ConvertsAmountToPrimaryAmount::convert($params);
    }
}
