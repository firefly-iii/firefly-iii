<?php
/**
 * JournalDestroyService.php
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

namespace FireflyIII\Services\Internal\Destroy;

use DB;
use Exception;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionJournalMeta;
use Log;

/**
 * @codeCoverageIgnore
 * Class JournalDestroyService
 */
class JournalDestroyService
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    public function destroy(TransactionJournal $journal): void
    {
        try {
            /** @var Transaction $transaction */
            foreach ($journal->transactions()->get() as $transaction) {
                Log::debug(sprintf('Will now delete transaction #%d', $transaction->id));
                $transaction->delete();
            }

            // also delete journal_meta entries.
            /** @var TransactionJournalMeta $meta */
            foreach ($journal->transactionJournalMeta()->get() as $meta) {
                Log::debug(sprintf('Will now delete meta-entry #%d', $meta->id));
                $meta->delete();
            }

            // also delete attachments.
            /** @var Attachment $attachment */
            foreach ($journal->attachments()->get() as $attachment) {
                $attachment->delete();
            }

            // delete all from 'budget_transaction_journal'
            DB::table('budget_transaction_journal')
              ->where('transaction_journal_id', $journal->id)->delete();

            // delete all from 'category_transaction_journal'
            DB::table('category_transaction_journal')
              ->where('transaction_journal_id', $journal->id)->delete();

            // delete all from 'tag_transaction_journal'
            DB::table('tag_transaction_journal')
              ->where('transaction_journal_id', $journal->id)->delete();

            // delete all links:
            TransactionJournalLink::where('source_id', $journal->id)->delete();
            TransactionJournalLink::where('destination_id', $journal->id)->delete();

            // delete all notes
            $journal->notes()->delete();

            // update events
            $journal->piggyBankEvents()->update(['transaction_journal_id' => null]);



            $journal->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete bill: %s', $e->getMessage())); // @codeCoverageIgnore
        }

    }

}
