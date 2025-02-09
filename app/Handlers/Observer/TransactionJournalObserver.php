<?php

/*
 * TransactionJournalObserver.php
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

use FireflyIII\Models\Attachment;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;

/**
 * Class TransactionJournalObserver
 */
class TransactionJournalObserver
{
    public function deleting(TransactionJournal $transactionJournal): void
    {
        app('log')->debug('Observe "deleting" of a transaction journal.');

        $repository = app(AttachmentRepositoryInterface::class);
        $repository->setUser($transactionJournal->user);


        // to make sure the listener doesn't get back to use and loop
        TransactionJournal::withoutEvents(static function () use ($transactionJournal): void {
            foreach ($transactionJournal->transactions()->get() as $transaction) {
                $transaction->delete();
            }
        });
        /** @var Attachment $attachment */
        foreach ($transactionJournal->attachments()->get() as $attachment) {
            $repository->destroy($attachment);
        }
        $transactionJournal->locations()->delete();
        $transactionJournal->sourceJournalLinks()->delete();
        $transactionJournal->destJournalLinks()->delete();
        $transactionJournal->auditLogEntries()->delete();
    }
}
