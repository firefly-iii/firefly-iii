<?php
declare(strict_types = 1);
/**
 * BillScanner.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Events;


use FireflyIII\Models\TransactionJournal;

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
        /** @var \FireflyIII\Repositories\Bill\BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $list       = $journal->user->bills()->where('active', 1)->where('automatch', 1)->get();

        /** @var \FireflyIII\Models\Bill $bill */
        foreach ($list as $bill) {
            $repository->scan($bill, $journal);
        }
    }

}
