<?php
/**
 * CategoryRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class CategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool
    {
        $category->delete();

        return true;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($accounts)->setCategories($categories);
        $set = $collector->getJournals();
        $sum = strval($set->sum('transaction_amount'));

        return $sum;
    }

    /**
     * Find a category
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId): Category
    {
        $category = $this->user->categories()->find($categoryId);
        if (is_null($category)) {
            $category = new Category;
        }

        return $category;
    }

    /**
     * Find a category
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name): Category
    {
        $categories = $this->user->categories()->get(['categories.*']);
        foreach ($categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return new Category;
    }

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function firstUseDate(Category $category): Carbon
    {
        $first = new Carbon;

        /** @var TransactionJournal $firstJournal */
        $firstJournal = $category->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.date']);

        // if transaction journal exists and date is before $first, then
        // new date:
        if (!is_null($firstJournal) && $firstJournal->date->lessThanOrEqualTo($first)) {
            $first = $firstJournal->date;
        }

        // check transactions:
        $firstTransaction = $category->transactions()
                                     ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                     ->orderBy('transaction_journals.date', 'ASC')->first(['transaction_journals.date']);


        // transaction exists, and date is before $first, this date becomes first.
        if (!is_null($firstTransaction) && Carbon::parse($firstTransaction->date)->lessThanOrEqualTo($first)) {
            $first = new Carbon($firstTransaction->date);
        }

        return $first;
    }

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->categories()->orderBy('name', 'ASC')->get();
        $set = $set->sortBy(
            function (Category $category) {
                return strtolower($category->name);
            }
        );

        return $set;
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function lastUseDate(Category $category, Collection $accounts): Carbon
    {
        $last = null;

        /** @var TransactionJournal $first */
        $lastJournalQuery = $category->transactionJournals()->orderBy('date', 'DESC');

        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $lastJournalQuery->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $lastJournalQuery->whereIn('t.account_id', $ids);
        }

        $lastJournal = $lastJournalQuery->first(['transaction_journals.*']);

        if ($lastJournal) {
            $last = $lastJournal->date;
        }

        // check transactions:

        $lastTransactionQuery = $category->transactions()
                                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                         ->orderBy('transaction_journals.date', 'DESC');
        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $lastTransactionQuery->whereIn('transactions.account_id', $ids);
        }

        $lastTransaction = $lastTransactionQuery->first(['transaction_journals.*']);
        if (!is_null($lastTransaction) && ((!is_null($last) && $lastTransaction->date < $last) || is_null($last))) {
            $last = new Carbon($lastTransaction->date);
        }

        if (is_null($last)) {
            return new Carbon('1900-01-01');
        }

        return $last;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Category $category */
        foreach ($categories as $category) {
            $data[$category->id] = [
                'name'    => $category->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getJournals();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (bccomp($transaction->transaction_amount, '0') === 1) {
                continue;
            }
            $categoryId                          = max(intval($transaction->transaction_journal_category_id), intval($transaction->transaction_category_id));
            $date                                = $transaction->date->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpensesNoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $transactions = $collector->getJournals();
        $result       = [
            'entries' => [],
            'name'    => strval(trans('firefly.no_category')),
            'sum'     => '0',
        ];

        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (bccomp($transaction->transaction_amount, '0') === 1) {
                continue;
            }
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }

        return $result;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Category $category */
        foreach ($categories as $category) {
            $data[$category->id] = [
                'name'    => $category->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getJournals();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if negative, skip:
            if (bccomp($transaction->transaction_amount, '0') === -1) {
                continue;
            }
            $categoryId                          = max(intval($transaction->transaction_journal_category_id), intval($transaction->transaction_category_id));
            $date                                = $transaction->date->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncomeNoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        Log::debug('Now in periodIncomeNoCategory()');
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $transactions = $collector->getJournals();
        $result       = [
            'entries' => [],
            'name'    => strval(trans('firefly.no_category')),
            'sum'     => '0',
        ];
        Log::debug('Looping transactions..');
        foreach ($transactions as $transaction) {

            // if negative, skip:
            if (bccomp($transaction->transaction_amount, '0') === -1) {
                continue;
            }
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }
        Log::debug('Done looping transactions..');
        Log::debug('Finished periodIncomeNoCategory()');

        return $result;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCategories($categories);


        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if ($accounts->count() === 0) {
            $collector->setAllAssetAccounts();
        }


        $set = $collector->getJournals();
        $sum = strval($set->sum('transaction_amount'));

        return $sum;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCategory();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if ($accounts->count() === 0) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getJournals();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        $sum = strval($set->sum('transaction_amount'));

        return $sum;
    }

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data): Category
    {
        $newCategory = Category::firstOrCreateEncrypted(
            [
                'user_id' => $this->user->id,
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
    public function update(Category $category, array $data): Category
    {
        // update the account:
        $category->name = $data['name'];
        $category->save();

        return $category;
    }

}
