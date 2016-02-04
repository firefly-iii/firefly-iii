<?php
/**
 * JournalCollector.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class JournalCollector
 *
 * @package FireflyIII\Export\Collector
 */
class JournalCollector
{
    /** @var Collection */
    private $accounts;
    /** @var  Carbon */
    private $end;
    /** @var  Carbon */
    private $start;
    /** @var  User */
    private $user;

    /**
     * JournalCollector constructor.
     *
     * @param Collection $accounts
     * @param User       $user
     * @param Carbon     $start
     * @param Carbon     $end
     */
    public function __construct(Collection $accounts, User $user, Carbon $start, Carbon $end)
    {
        $this->accounts = $accounts;
        $this->user     = $user;
        $this->start    = $start;
        $this->end      = $end;
    }

    /**
     * @return Collection
     */
    public function collect()
    {
        // get all the journals:
        $ids = $this->accounts->pluck('id')->toArray();

        return $this->user->transactionjournals()
                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->whereIn('transactions.account_id', $ids)
                          ->before($this->end)
                          ->after($this->start)
                          ->orderBy('transaction_journals.date')
                          ->get(['transaction_journals.*']);
    }

}