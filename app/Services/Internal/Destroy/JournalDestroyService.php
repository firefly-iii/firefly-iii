<?php
/**
 * JournalDestroyService.php
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

namespace FireflyIII\Services\Internal\Destroy;

use Exception;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
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
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
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
            $journal->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete bill: %s', $e->getMessage())); // @codeCoverageIgnore
        }

    }

}
