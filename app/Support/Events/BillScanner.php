<?php
/**
 * BillScanner.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Events;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;

/**
 * Class BillScanner
 *
 * @package FireflyIII\Support\Events
 */
class BillScanner
{
    /**
     * @param TransactionJournal $journal
     */
    public static function scan(TransactionJournal $journal)
    {
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $list       = $journal->user->bills()->where('active', 1)->where('automatch', 1)->get();

        /** @var \FireflyIII\Models\Bill $bill */
        foreach ($list as $bill) {
            $repository->scan($bill, $journal);
        }
    }

}
