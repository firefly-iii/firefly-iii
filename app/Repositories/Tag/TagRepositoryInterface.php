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
