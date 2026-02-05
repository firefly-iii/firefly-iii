<?php

declare(strict_types=1);

/*
 * ProcessesUpdatedTransactionGroup.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\UpdatedSingleTransactionGroup;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Facades\Log;

class ProcessesUpdatedTransactionGroup
{
    use SupportsGroupProcessingTrait;

    public function handle(UpdatedSingleTransactionGroup $event): void
    {
        Log::debug(sprintf('User called %s', get_class($event)));
        $this->unifyAccounts($event);

        Log::debug(sprintf('Transaction journal count is %d', $event->objects->transactionJournals->count()));
        if (!$event->flags->applyRules) {
            Log::debug(sprintf('Will NOT process rules for %d journal(s)', $event->objects->transactionJournals->count()));
        }
        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $event->objects->transactionJournals->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $event->objects->transactionJournals->count()));
        }

        if ($event->flags->applyRules) {
            $this->processRules($event->objects->transactionJournals, 'update-journal');
        }
        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($event->objects->accounts);
        }
        if ($event->flags->fireWebhooks) {
            $this->createWebhookMessages($event->objects->transactionGroups, WebhookTrigger::UPDATE_TRANSACTION);
        }
        $this->removePeriodStatistics($event->objects);
        $this->recalculateRunningBalance($event->objects);

        Log::debug('Done with handle() for UpdatedSingleTransactionGroup');
    }

    /**
     * This method will make sure all source / destination accounts are the same.
     */
    protected function unifyAccounts(UpdatedSingleTransactionGroup $updatedGroupEvent): void
    {
        Log::debug('Now in unifyAccounts()');

        /** @var TransactionGroup $group */
        foreach ($updatedGroupEvent->objects->transactionGroups as $group) {
            $this->unifyAccountsForGroup($group);
        }
        Log::debug('Done with unifyAccounts()');
    }

    private function unifyAccountsForGroup(TransactionGroup $group): void
    {
        if (1 === $group->transactionJournals->count()) {
            Log::debug('Nothing to do in unifyAccounts()');

            return;
        }

        // first journal:
        /** @var null|TransactionJournal $first */
        $first         = $group
            ->transactionJournals()
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->orderBy('transaction_journals.description', 'DESC')
            ->first()
        ;

        if (null === $first) {
            Log::warning(sprintf('Group #%d has no transaction journals.', $group->id));

            return;
        }

        $all           = $group->transactionJournals()->get()->pluck('id')->toArray();

        /** @var Account $sourceAccount */
        $sourceAccount = $first->transactions()->where('amount', '<', '0')->first()->account;

        /** @var Account $destAccount */
        $destAccount   = $first->transactions()->where('amount', '>', '0')->first()->account;

        $type          = $first->transactionType->type;
        if (TransactionTypeEnum::TRANSFER->value === $type || TransactionTypeEnum::WITHDRAWAL->value === $type) {
            // set all source transactions to source account:
            Transaction::whereIn('transaction_journal_id', $all)->where('amount', '<', 0)->update(['account_id' => $sourceAccount->id]);
        }
        if (TransactionTypeEnum::TRANSFER->value === $type || TransactionTypeEnum::DEPOSIT->value === $type) {
            // set all destination transactions to destination account:
            Transaction::whereIn('transaction_journal_id', $all)->where('amount', '>', 0)->update(['account_id' => $destAccount->id]);
        }
    }
}
