<?php

/*
 * AccountObserver.php
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

use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountObserver
 */
class AccountObserver
{
    public function created(Account $account): void
    {
        //        Log::debug('Observe "created" of an account.');
        $this->updateNativeAmount($account);
    }

    private function updateNativeAmount(Account $account): void
    {
        if (!Amount::convertToNative($account->user)) {
            return;
        }
        $userCurrency = app('amount')->getNativeCurrencyByUserGroup($account->user->userGroup);
        $repository   = app(AccountRepositoryInterface::class);
        $currency     = $repository->getAccountCurrency($account);
        if (null !== $currency && $currency->id !== $userCurrency->id && '' !== (string) $account->virtual_balance && 0 !== bccomp($account->virtual_balance, '0')) {
            $converter                       = new ExchangeRateConverter();
            $converter->setUserGroup($account->user->userGroup);
            $converter->setIgnoreSettings(true);
            $account->native_virtual_balance = $converter->convert($currency, $userCurrency, today(), $account->virtual_balance);

        }
        if ('' === (string) $account->virtual_balance || (0 === bccomp($account->virtual_balance, '0'))) {
            $account->virtual_balance        = null;
            $account->native_virtual_balance = null;
        }
        $account->saveQuietly();
        // Log::debug('Account native virtual balance is updated.');
    }

    /**
     * Also delete related objects.
     */
    public function deleting(Account $account): void
    {
        app('log')->debug('Observe "deleting" of an account.');

        $repository = app(AttachmentRepositoryInterface::class);
        $repository->setUser($account->user);

        DB::table('account_piggy_bank')->where('account_id', $account->id)->delete();

        /** @var Attachment $attachment */
        foreach ($account->attachments()->get() as $attachment) {
            $repository->destroy($attachment);
        }

        $journalIds = Transaction::where('account_id', $account->id)->get(['transactions.transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $groupIds   = TransactionJournal::whereIn('id', $journalIds)->get(['transaction_journals.transaction_group_id'])->pluck('transaction_group_id')->toArray();
        if (count($journalIds) > 0) {
            Transaction::whereIn('transaction_journal_id', $journalIds)->delete();
            TransactionJournal::whereIn('id', $journalIds)->delete();
        }
        if (count($groupIds) > 0) {
            TransactionGroup::whereIn('id', $groupIds)->delete();
        }

        Log::debug(sprintf('Delete %d journal(s)', count($journalIds)));
        Log::debug(sprintf('Delete %d group(s)', count($groupIds)));

        $account->notes()->delete();
        $account->locations()->delete();
    }

    public function updated(Account $account): void
    {
        //        Log::debug('Observe "updated" of an account.');
        $this->updateNativeAmount($account);
    }
}
