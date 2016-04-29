<?php
/**
 * JournalInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Crud\Split;


use FireflyIII\Models\Transaction;
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
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function storeJournal(array $data) : TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $transaction
     *
     * @return Collection
     */
    public function storeTransaction(TransactionJournal $journal, array $transaction): Collection;
}