<?php

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Shared\ComponentRepository;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class SingleCategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class SingleCategoryRepository extends ComponentRepository implements SingleCategoryRepositoryInterface
{

    /**
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function balanceInPeriod(Category $category, Carbon $start, Carbon $end, Collection $accounts)
    {
        return $this->commonBalanceInPeriod($category, $start, $end, $accounts);
    }


    /**
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category)
    {
        return $category->transactionJournals()->count();

    }

    /**
     * @param Category $category
     *
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return int
     */
    public function countJournalsInRange(Category $category, Carbon $start, Carbon $end)
    {
        return $category->transactionJournals()->before($end)->after($start)->count();
    }

    /**
     * @param Category $category
     *
     * @return boolean
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return true;
    }

    /**
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Category $category, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties; // we must cache this.
        $cache->addProperty($category->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('earnedInPeriod');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $sum = $category->transactionjournals()->transactionTypes([TransactionType::DEPOSIT])->before($end)->after($start)->get(['transaction_journals.*'])
                        ->sum(
                            'amount'
                        );

        $cache->store($sum);

        return $sum;
    }


    /**
     * Calculate how much is earned in this period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriodForAccounts(Category $category, Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        $sum
            = $category
            ->transactionjournals()
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->before($end)
            ->whereIn('transactions.account_id', $accountIds)
            ->transactionTypes([TransactionType::DEPOSIT])
            ->after($start)
            ->get(['transaction_journals.*'])
            ->sum('amount');

        return $sum;

    }

    /**
     *
     * Corrected for tags.
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return float
     */
    public function earnedOnDaySum(Category $category, Carbon $date)
    {
        return $category->transactionjournals()->transactionTypes([TransactionType::DEPOSIT])->onDate($date)->get(['transaction_journals.*'])->sum('amount');
    }

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function getFirstActivityDate(Category $category)
    {
        /** @var TransactionJournal $first */
        $first = $category->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($first) {
            return $first->date;
        }

        return new Carbon;

    }

    /**
     * @param Category $category
     * @param int      $page
     *
     * @return Collection
     */
    public function getJournals(Category $category, $page)
    {
        $offset = $page > 0 ? $page * 50 : 0;

        return $category->transactionJournals()->withRelevantData()->take(50)->offset($offset)
                        ->orderBy('transaction_journals.date', 'DESC')
                        ->orderBy('transaction_journals.order', 'ASC')
                        ->orderBy('transaction_journals.id', 'DESC')
                        ->get(
                            ['transaction_journals.*']
                        );

    }

    /**
     * @param Category $category
     * @param int      $page
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getJournalsInRange(Category $category, $page, Carbon $start, Carbon $end)
    {
        $offset = $page > 0 ? $page * 50 : 0;

        return $category->transactionJournals()
                        ->after($start)
                        ->before($end)
                        ->withRelevantData()->take(50)->offset($offset)
                        ->orderBy('transaction_journals.date', 'DESC')
                        ->orderBy('transaction_journals.order', 'ASC')
                        ->orderBy('transaction_journals.id', 'DESC')
                        ->get(
                            ['transaction_journals.*']
                        );
    }


    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function getLatestActivity(Category $category)
    {
        $latest = $category->transactionjournals()
                           ->orderBy('transaction_journals.date', 'DESC')
                           ->orderBy('transaction_journals.order', 'ASC')
                           ->orderBy('transaction_journals.id', 'DESC')
                           ->first();
        if ($latest) {
            return $latest->date;
        }

        return null;
    }


    /**
     * Calculates how much is spent in this period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodForAccounts(Category $category, Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }

        $sum
            = $category
            ->transactionjournals()
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->after($start)
            ->before($end)
            ->whereIn('transactions.account_id', $accountIds)
            ->transactionTypes([TransactionType::WITHDRAWAL])
            ->get(['transaction_journals.*'])
            ->sum('amount');

        return $sum;

    }

    /**
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties; // we must cache this.
        $cache->addProperty($category->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('spentInPeriod');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $sum = $category->transactionjournals()->transactionTypes([TransactionType::WITHDRAWAL])->before($end)->after($start)->get(['transaction_journals.*'])
                        ->sum(
                            'amount'
                        );

        $cache->store($sum);

        return $sum;
    }


    /**
     * Corrected for tags
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return string
     */
    public function spentOnDaySum(Category $category, Carbon $date)
    {
        return $category->transactionjournals()->transactionTypes([TransactionType::WITHDRAWAL])->onDate($date)->get(['transaction_journals.*'])->sum('amount');
    }

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data)
    {
        $newCategory = new Category(
            [
                'user_id' => $data['user'],
                'name'    => $data['name'],
            ]
        );
        $newCategory->save();

        return $newCategory;
    }

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data)
    {
        // update the account:
        $category->name = $data['name'];
        $category->save();

        return $category;
    }


}