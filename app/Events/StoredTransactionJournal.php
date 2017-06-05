<?php
/**
 * StoredTransactionJournal.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class StoredTransactionJournal
 *
 * @package FireflyIII\Events
 */
class StoredTransactionJournal extends Event
{

    use SerializesModels;

    /** @var TransactionJournal */
    public $journal;
    /** @var int */
    public $piggyBankId;

    /**
     * Create a new event instance.
     *
     * @param TransactionJournal $journal
     * @param int                $piggyBankId
     */
    public function __construct(TransactionJournal $journal, int $piggyBankId)
    {
        //
        $this->journal     = $journal;
        $this->piggyBankId = $piggyBankId;

    }

}
