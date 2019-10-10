<?php
/**
 * UserNavigation.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Log;

/**
 * Trait UserNavigation
 *
 */
trait UserNavigation
{

    //if (!$this->isEditableAccount($account)) {
    //            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
    //        }

    /**
     * Will return false if you cant edit this account type.
     *
     * @param Account $account
     *
     * @return bool
     */
    protected function isEditableAccount(Account $account): bool
    {
        $editable = [AccountType::EXPENSE, AccountType::REVENUE, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
        $type     = $account->accountType->type;

        return in_array($type, $editable, true);
    }

    /**
     * @param TransactionGroup $group
     *
     * @return bool
     */
    protected function isEditableGroup(TransactionGroup $group): bool
    {
        /** @var TransactionJournal $journal */
        $journal = $group->transactionJournals()->first();
        if (null === $journal) {
            return false;
        }
        $type     = $journal->transactionType->type;
        $editable = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::DEPOSIT];

        return in_array($type, $editable, true);
    }

    /**
     * @param TransactionGroup $group
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectGroupToAccount(TransactionGroup $group)
    {
        /** @var TransactionJournal $journal */
        $journal = $group->transactionJournals()->first();
        if (null === $journal) {
            Log::error(sprintf('No journals in group #%d', $group->id));

            return redirect(route('index'));
        }
        // prefer redirect to everything but expense and revenue:
        $transactions = $journal->transactions;
        $ignore       = [AccountType::REVENUE, AccountType::EXPENSE, AccountType::RECONCILIATION, AccountType::INITIAL_BALANCE];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $type = $transaction->account->accountType->type;
            if (!in_array($type, $ignore)) {
                return redirect(route('accounts.show', [$transaction->account_id]));
            }
        }

        return redirect(route('index'));
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectAccountToAccount(Account $account)
    {
        $type = $account->accountType->type;
        if (AccountType::RECONCILIATION === $type || AccountType::INITIAL_BALANCE === $type) {
            // reconciliation must be stored somewhere in this account's transactions.

            /** @var Transaction $transaction */
            $transaction = $account->transactions()->first();
            if (null === $transaction) {
                Log::error(sprintf('Account #%d has no transactions. Dont know where it belongs.', $account->id));
                session()->flash('error', trans('firefly.cant_find_redirect_account'));

                return redirect(route('index'));
            }
            $journal = $transaction->transactionJournal;
            /** @var Transaction $other */
            $other = $journal->transactions()->where('id', '!=', $transaction->id)->first();
            if (null === $other) {
                Log::error(sprintf('Account #%d has no valid journals. Dont know where it belongs.', $account->id));
                session()->flash('error', trans('firefly.cant_find_redirect_account'));

                return redirect(route('index'));
            }

            return redirect(route('accounts.show', [$other->account_id]));
        }

        return redirect(route('index'));
    }


    /**
     * Functionality:.
     *
     * - If the $identifier contains the word "delete" then a remembered uri with the text "/show/" in it will not be returned but instead the index (/)
     *   will be returned.
     * - If the remembered uri contains "jscript/" the remembered uri will not be returned but instead the index (/) will be returned.
     *
     * @param string $identifier
     *
     * @return string
     */
    protected function getPreviousUri(string $identifier): string
    {
        Log::debug(sprintf('Trying to retrieve URL stored under "%s"', $identifier));
        $uri = (string)session($identifier);
        Log::debug(sprintf('The URI is %s', $uri));

        if (!(false === strpos($uri, 'jscript'))) {
            $uri = $this->redirectUri; // @codeCoverageIgnore
            Log::debug(sprintf('URI is now %s (uri contains jscript)', $uri));
        }

        Log::debug(sprintf('Return direct link %s', $uri));
        return $uri;
    }
    //
    //    /**
    //     * Redirect to asset account that transaction belongs to.
    //     *
    //     * @param TransactionGroup $group
    //     *
    //     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
    //     * @codeCoverageIgnore
    //     */
    //    protected function redirectToAccount(TransactionGroup $group)
    //    {
    //        $journals = $group->transactionJournals;
    //        $first    = $journals->first();
    //
    //        if (null === $first) {
    //            return redirect(route('index'));
    //        }
    //
    //        $valid        = [AccountType::DEFAULT, AccountType::ASSET];
    //        $transactions = $journal->transactions;
    //        /** @var Transaction $transaction */
    //        foreach ($transactions as $transaction) {
    //            $account = $transaction->account;
    //            if (in_array($account->accountType->type, $valid, true)) {
    //                return redirect(route('accounts.show', [$account->id]));
    //            }
    //        }
    //        // @codeCoverageIgnoreStart
    //        session()->flash('error', (string)trans('firefly.cannot_redirect_to_account'));
    //
    //        return redirect(route('index'));
    //        // @codeCoverageIgnoreEnd
    //    }
    //
    //    /**
    //     * @param Account $account
    //     *
    //     * @return RedirectResponse|\Illuminate\Routing\Redirector
    //     * @codeCoverageIgnore
    //     */
    //    protected function redirectToOriginalAccount(Account $account)
    //    {
    //        /** @var Transaction $transaction */
    //        $transaction = $account->transactions()->first();
    //        if (null === $transaction) {
    //            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => e($account->name), 'id' => $account->id]));
    //            Log::error(sprintf('Expected a transaction. Account #%d has none. BEEP, error.', $account->id));
    //
    //            return redirect(route('index'));
    //        }
    //
    //        $journal = $transaction->transactionJournal;
    //        /** @var Transaction $opposingTransaction */
    //        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();
    //
    //        if (null === $opposingTransaction) {
    //            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => e($account->name), 'id' => $account->id]));
    //            Log::error(sprintf('Expected an opposing transaction. Account #%d has none. BEEP, error.', $account->id));
    //        }
    //
    //        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    //    }

    /**
     * @param string $identifier
     *
     * @return string|null
     */
    protected function rememberPreviousUri(string $identifier): ?string
    {
        $return = null;
        /** @var ViewErrorBag $errors */
        $errors    = session()->get('errors');
        $forbidden = ['json'];
        if ((null === $errors || (null !== $errors && 0 === $errors->count())) && !Str::contains($return, $forbidden)) {
            $return = app('url')->previous();
            session()->put($identifier, $return);
        }
        return $return;
    }
}
