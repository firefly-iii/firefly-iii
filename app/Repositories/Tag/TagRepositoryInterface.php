<?php
declare(strict_types = 1);

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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function allCoveredByBalancingActs(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    public function connect(TransactionJournal $journal, Tag $tag): bool;

    /**
     * @deprecated
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
     * @return string
     */
    public function coveredByBalancingActs(Account $account, Carbon $start, Carbon $end): string;

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    public function destroy(Tag $tag): bool;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag;

    /**
     * Can a tag become an advance payment?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowAdvance(Tag $tag): bool;

    /**
     * Can a tag become a balancing act?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function tagAllowBalancing(Tag $tag): bool;

    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag;
}
