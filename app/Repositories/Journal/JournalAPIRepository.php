<?php

/**
 * JournalAPIRepository.php
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

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Attachment;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Class JournalAPIRepository
 */
class JournalAPIRepository implements JournalAPIRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * Returns transaction by ID. Used to validate attachments.
     */
    public function findTransaction(int $transactionId): ?Transaction
    {
        return Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('transactions.id', $transactionId)
            ->first(['transactions.*'])
        ;
    }

    /**
     * TODO pretty sure method duplicated.
     *
     * Return all attachments for journal.
     */
    public function getAttachments(TransactionJournal $journal): Collection
    {
        $set  = $journal->attachments;

        $disk = Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) {
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes_text  = null !== $notes ? $notes->text : ''; // TODO should not set notes like this.

                return $attachment;
            }
        );
    }

    public function getJournalLinks(TransactionJournal $journal): Collection
    {
        $collection = $journal->destJournalLinks()->get();

        return $journal->sourceJournalLinks()->get()->merge($collection);
    }

    /**
     * Get all piggy bank events for a journal.
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection
    {
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            static function (PiggyBankEvent $event): void { // @phpstan-ignore-line
                $event->piggyBank = PiggyBank::withTrashed()->find($event->piggy_bank_id);
            }
        );

        return $events;
    }
}
