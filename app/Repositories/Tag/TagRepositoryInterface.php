<?php

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;


/**
 * Interface TagRepositoryInterface
 *
 * @package FireflyIII\Repositories\Tag
 */
interface TagRepositoryInterface
{


    /**
     * This method scans the transaction journals from or to the given asset account
     * and checks if these are part of a balancing act. If so, it will sum up the amounts
     * transferred into the balancing act (if any) and return this amount.
     *
     * This method effectively tells you the amount of money that has been balanced out
     * correctly in the given period for the given account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function coveredByBalancingActs(Account $account, Carbon $start, Carbon $end);

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data);

    /**
     * @return Collection
     */
    public function get();

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data);

    /**
     * @param Tag $tag
     *
     * @return boolean
     */
    public function destroy(Tag $tag);

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return boolean
     */
    public function connect(TransactionJournal $journal, Tag $tag);
}
