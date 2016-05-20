<?php
declare(strict_types = 1);

/**
 * TransactionStored.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */


namespace FireflyIII\Events;

use Illuminate\Queue\SerializesModels;

/**
 * Class TransactionJournalStored
 *
 * @package FireflyIII\Events
 */
class TransactionStored extends Event
{

    use SerializesModels;

    public $transaction = [];

    /**
     * Create a new event instance.
     *
     * @param array $transaction
     */
    public function __construct(array $transaction)
    {
        //
        $this->transaction = $transaction;
    }

}
