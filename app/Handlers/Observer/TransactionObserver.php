<?php

/*
 * TransactionObserver.php
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
use FireflyIII\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Class TransactionObserver
 */
class TransactionObserver
{
    public static bool $recalculate = true;

    public function created(Transaction $transaction): void
    {
        Log::debug(sprintf('Observed creation of Transaction #%d.', $transaction->id));
        $this->updatePrimaryCurrencyAmount($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        $this->updatePrimaryCurrencyAmount($transaction);
    }

    private function updatePrimaryCurrencyAmount(Transaction $transaction): void
    {
        // convert "amount" to "native_amount"
        $params                     = new ConversionParameters();
        $params->user               = $transaction->transactionJournal->user;
        $params->model              = $transaction;
        $params->originalCurrency   = $transaction->transactionCurrency;
        $params->amountField        = 'amount';
        $params->date               = $transaction->transactionJournal->date;
        $params->primaryAmountField = 'native_amount';
        ConvertsAmountToPrimaryAmount::convert($params);

        // convert "foreign_amount" to "native_foreign_amount"
        $params                     = new ConversionParameters();
        $params->user               = $transaction->transactionJournal->user;
        $params->model              = $transaction;
        $params->originalCurrency   = $transaction->foreignCurrency;
        $params->date               = $transaction->transactionJournal->date;
        $params->amountField        = 'foreign_amount';
        $params->primaryAmountField = 'native_foreign_amount';
        ConvertsAmountToPrimaryAmount::convert($params);
    }
}
