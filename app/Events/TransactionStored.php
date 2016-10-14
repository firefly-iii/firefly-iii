<?php
/**
 * TransactionStored.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
