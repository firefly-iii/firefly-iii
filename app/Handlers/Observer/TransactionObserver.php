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

use FireflyIII\Models\Transaction;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Support\Facades\Log;

/**
 * Class TransactionObserver
 */
class TransactionObserver
{
    public function deleting(?Transaction $transaction): void
    {
        app('log')->debug('Observe "deleting" of a transaction.');
        $transaction?->transactionJournal?->delete();
    }

    public function updated(Transaction $transaction): void
    {
        Log::debug('Observe "updated" of a transaction.');
        if (config('firefly.feature_flags.running_balance_column')) {
            if (1 === bccomp($transaction->amount, '0')) {
                Log::debug('Trigger recalculateForJournal');
                AccountBalanceCalculator::recalculateForJournal($transaction->transactionJournal);
            }
        }
    }

    public function created(Transaction $transaction): void
    {
        Log::debug('Observe "created" of a transaction.');
        if (config('firefly.feature_flags.running_balance_column')) {
            if (1 === bccomp($transaction->amount, '0')) {
                Log::debug('Trigger recalculateForJournal');
                AccountBalanceCalculator::recalculateForJournal($transaction->transactionJournal);
            }
        }
    }
}
