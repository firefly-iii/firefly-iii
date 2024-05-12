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

use DB;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use stdClass;

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
        app('log')->debug('Observe "updated" of a transaction.');
        // refresh account balance:
        /** @var stdClass $result */
        $result = Transaction::groupBy(['account_id', 'transaction_currency_id'])->where('account_id', $transaction->account_id)->first(['account_id', 'transaction_currency_id', DB::raw('SUM(amount) as amount_sum')]);
        if (null !== $result) {
            $account  = (int) $result->account_id;
            $currency = (int) $result->transaction_currency_id;
            $sum      = $result->amount_sum;

            AccountBalance::updateOrCreate(['title' => 'balance', 'account_id' => $account, 'transaction_currency_id' => $currency], ['balance' => $sum]);
        }

    }
}
