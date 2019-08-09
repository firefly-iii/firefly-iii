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
use Illuminate\Support\ViewErrorBag;
use Log;

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

        // "forbidden" words for specific identifiers:
        // if these are in the previous URI, don't refer back there.
        //        $array     = [
        //            'accounts.delete.uri'          => '/accounts/show/',
        //            'transactions.delete.uri'      => '/transactions/show/',
        //            'attachments.delete.uri'       => '/attachments/show/',
        //            'bills.delete.uri'             => '/bills/show/',
        //            'budgets.delete.uri'           => '/budgets/show/',
        //            'categories.delete.uri'        => '/categories/show/',
        //            'currencies.delete.uri'        => '/currencies/show/',
        //            'piggy-banks.delete.uri'       => '/piggy-banks/show/',
        //            'tags.delete.uri'              => '/tags/show/',
        //            'rules.delete.uri'             => '/rules/edit/',
        //            'transactions.mass-delete.uri' => '/transactions/show/',
        //        ];
        //$forbidden = $array[$identifier] ?? '/show/';
        //Log::debug(sprintf('The forbidden word for %s is "%s"', $identifier, $forbidden));


        //        if (
        //            !(false === strpos($identifier, 'delete'))
        //            && !(false === strpos($uri, $forbidden))) {
        //            $uri = $this->redirectUri;
        //            //Log::debug(sprintf('URI is now %s (identifier contains "delete")', $uri));
        //        }


        // more debug notes:
        //Log::debug(sprintf('strpos($identifier, "delete"): %s', var_export(strpos($identifier, 'delete'), true)));
        //Log::debug(sprintf('strpos($uri, $forbidden): %s', var_export(strpos($uri, $forbidden), true)));
        Log::debug(sprintf('Return direct link %s', $uri));
        return $uri;
    }

    /**
     * Redirect to asset account that transaction belongs to.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @codeCoverageIgnore
     */
    protected function redirectToAccount(TransactionJournal $journal)
    {
        $valid        = [AccountType::DEFAULT, AccountType::ASSET];
        $transactions = $journal->transactions;
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $account = $transaction->account;
            if (in_array($account->accountType->type, $valid, true)) {
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
     * @codeCoverageIgnore
     */
    protected function redirectToOriginalAccount(Account $account)
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->first();
        if (null === $transaction) {
            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => e($account->name), 'id' => $account->id]));
            Log::error(sprintf('Expected a transaction. Account #%d has none. BEEP, error.', $account->id));

            return redirect(route('index'));
        }

        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposingTransaction */
        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();

        if (null === $opposingTransaction) {
            app('session')->flash('error', trans('firefly.account_missing_transaction', ['name' => e($account->name), 'id' => $account->id]));
            Log::error(sprintf('Expected an opposing transaction. Account #%d has none. BEEP, error.', $account->id));
        }

        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    }

    /**
     * @param string $identifier
     *
     * @return string|null
     */
    protected function rememberPreviousUri(string $identifier): ?string
    {
        $return = null;
        /** @var ViewErrorBag $errors */
        $errors = session()->get('errors');
        if (null === $errors || (null !== $errors && 0 === $errors->count())) {
            $return = app('url')->previous();

            // TODO URL might not be one we *want* to remember.

            session()->put($identifier, $return);
            //Log::debug(sprintf('Will put previous URI in cache under key %s: %s', $identifier, $url));
            //return;
        }
        //Log::debug(sprintf('The users session contains errors somehow so we will not remember the URI!: %s', var_export($errors, true)));
        return $return;
    }
}
