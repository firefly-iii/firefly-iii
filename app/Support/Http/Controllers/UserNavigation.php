<?php
/**
 * UserNavigation.php
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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Http\RedirectResponse;
use Log;
use URL;

/**
 * Trait UserNavigation
 *
 */
trait UserNavigation
{
    /**
     * Functionality:.
     *
     * - If the $identifier contains the word "delete" then a remembered uri with the text "/show/" in it will not be returned but instead the index (/)
     *   will be returned.
     * - If the remembered uri contains "javascript/" the remembered uri will not be returned but instead the index (/) will be returned.
     *
     * @param string $identifier
     *
     * @return string
     */
    protected function getPreviousUri(string $identifier): string
    {
        $uri = (string)session($identifier);
        if (!(false === strpos($identifier, 'delete')) && !(false === strpos($uri, '/show/'))) {
            $uri = $this->redirectUri;
        }
        if (!(false === strpos($uri, 'jscript'))) {
            $uri = $this->redirectUri; // @codeCoverageIgnore
        }

        return $uri;
    }

    /**
     * Redirect to asset account that transaction belongs to.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectToAccount(TransactionJournal $journal)
    {
        $valid        = [AccountType::DEFAULT, AccountType::ASSET];
        $transactions = $journal->transactions;
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $account = $transaction->account;
            if (\in_array($account->accountType->type, $valid, true)) {
                return redirect(route('accounts.show', [$account->id]));
            }
        }
        // @codeCoverageIgnoreStart
        session()->flash('error', (string)trans('firefly.cannot_redirect_to_account'));

        return redirect(route('index'));
        // @codeCoverageIgnoreEnd
    }

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

    /**
     * Remember previous URL.
     *
     * @param string $identifier
     */
    protected function rememberPreviousUri(string $identifier): void
    {
        session()->put($identifier, URL::previous());
    }
}