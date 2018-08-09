<?php
/**
 * UserRedirection.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Log;

/**
 * Trait UserRedirection
 *
 * @package FireflyIII\Support\Http\Controllers
 */
trait UserRedirection
{
    /**
     * @param Account $account
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectToOriginalAccount(Account $account)
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->first();
        if (null === $transaction) {
            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => $account->name, 'id' => $account->id]));
            Log::error(sprintf('Expected a transaction. Account #%d has none. BEEP, error.', $account->id));

            return redirect(route('index'));
        }

        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposingTransaction */
        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();

        if (null === $opposingTransaction) {
            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => $account->name, 'id' => $account->id]));
            Log::error(sprintf('Expected an opposing transaction. Account #%d has none. BEEP, error.', $account->id));
        }

        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    }
}