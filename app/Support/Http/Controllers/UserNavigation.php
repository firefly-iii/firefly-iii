<?php

/**
 * UserNavigation.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

/**
 * Trait UserNavigation
 */
trait UserNavigation
{
    /**
     * Functionality:.
     *
     * - If the $identifier contains the word "delete" then a remembered url with the text "/show/" in it will not be
     * returned but instead the index (/) will be returned.
     * - If the remembered url contains "jscript/" the remembered url will not be returned but instead the index (/)
     * will be returned.
     */
    final protected function getPreviousUrl(string $identifier): string
    {
        app('log')->debug(sprintf('Trying to retrieve URL stored under "%s"', $identifier));
        $url = (string) session($identifier);
        app('log')->debug(sprintf('The URL is %s', $url));

        return app('steam')->getSafeUrl($url, route('index'));
    }

    /**
     * Will return false if you cant edit this account type.
     */
    final protected function isEditableAccount(Account $account): bool
    {
        $editable = [AccountTypeEnum::EXPENSE->value, AccountTypeEnum::REVENUE->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        $type     = $account->accountType->type;

        return in_array($type, $editable, true);
    }

    final protected function isEditableGroup(TransactionGroup $group): bool
    {
        /** @var null|TransactionJournal $journal */
        $journal  = $group->transactionJournals()->first();
        if (null === $journal) {
            return false;
        }
        $type     = $journal->transactionType->type;
        $editable = [TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::TRANSFER->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::RECONCILIATION->value];

        return in_array($type, $editable, true);
    }

    /**
     * @return Redirector|RedirectResponse
     */
    final protected function redirectAccountToAccount(Account $account)
    {
        $type = $account->accountType->type;
        if (AccountTypeEnum::RECONCILIATION->value === $type || AccountTypeEnum::INITIAL_BALANCE->value === $type || AccountTypeEnum::LIABILITY_CREDIT->value === $type) {
            // reconciliation must be stored somewhere in this account's transactions.

            /** @var null|Transaction $transaction */
            $transaction = $account->transactions()->first();
            if (null === $transaction) {
                app('log')->error(sprintf('Account #%d has no transactions. Dont know where it belongs.', $account->id));
                session()->flash('error', trans('firefly.cant_find_redirect_account'));

                return redirect(route('index'));
            }
            $journal     = $transaction->transactionJournal;

            /** @var null|Transaction $other */
            $other       = $journal->transactions()->where('id', '!=', $transaction->id)->first();
            if (null === $other) {
                app('log')->error(sprintf('Account #%d has no valid journals. Dont know where it belongs.', $account->id));
                session()->flash('error', trans('firefly.cant_find_redirect_account'));

                return redirect(route('index'));
            }

            return redirect(route('accounts.show', [$other->account_id]));
        }

        return redirect(route('index'));
    }

    /**
     * @return Redirector|RedirectResponse
     */
    final protected function redirectGroupToAccount(TransactionGroup $group)
    {
        /** @var null|TransactionJournal $journal */
        $journal      = $group->transactionJournals()->first();
        if (null === $journal) {
            app('log')->error(sprintf('No journals in group #%d', $group->id));

            return redirect(route('index'));
        }
        // prefer redirect to everything but expense and revenue:
        $transactions = $journal->transactions;
        $ignore       = [AccountTypeEnum::REVENUE->value, AccountTypeEnum::EXPENSE->value, AccountTypeEnum::RECONCILIATION->value, AccountTypeEnum::INITIAL_BALANCE->value];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $type = $transaction->account->accountType->type;
            if (!in_array($type, $ignore, true)) {
                return redirect(route('accounts.edit', [$transaction->account_id]));
            }
        }

        return redirect(route('index'));
    }

    final protected function rememberPreviousUrl(string $identifier): ?string
    {
        $return = app('steam')->getSafePreviousUrl();
        session()->put($identifier, $return);

        app('log')->debug(sprintf('rememberPreviousUrl: %s: "%s"', $identifier, $return));

        return $return;
    }
}
