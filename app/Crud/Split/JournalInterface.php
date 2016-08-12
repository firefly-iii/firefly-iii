<?php
/**
 * JournalInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Crud\Split;


use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Interface JournalInterface
 *
 * @package FireflyIII\Crud\Split
 */
interface JournalInterface
{
    /**
     * @param $journal
     *
     * @return bool
     */
    public function markAsComplete(TransactionJournal $journal);

    /**
     * @param TransactionJournal $journal
     * @param array              $transaction
     *
     * @return Collection
     */
    public function storeTransaction(TransactionJournal $journal, array $transaction): Collection;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateJournal(TransactionJournal $journal, array $data): TransactionJournal;
}
