<?php
/**
 * CategoryRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\CategoryDestroyService;
use FireflyIII\Services\Internal\Update\CategoryUpdateService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class CategoryRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param Category $category
     *
     * @return bool
     *

     */
    public function destroy(Category $category): bool
    {
        /** @var CategoryDestroyService $service */
        $service = app(CategoryDestroyService::class);
        $service->destroy($category);

        return true;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
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
        $set = $this->earnedInPeriodCollection($categories, $accounts, $start, $end);

        return (string)$set->sum('transaction_amount');
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function earnedInPeriodCollection(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        if (0 !== $accounts->count()) {
            $collector->setAccounts($accounts);
        }

        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setCategories($categories);

        return $collector->getTransactions();
    }

    /**
     * Find a category.
     *
     * @param string $name
     *
     * @return Category|null
     */
    public function findByName(string $name): ?Category
    {
        $categories = $this->user->categories()->get(['categories.*']);
        foreach ($categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Find a category or return NULL
     *
     * @param int $categoryId
     *
     * @return Category|null
     */
    public function findNull(int $categoryId): ?Category
    {
        return $this->user->categories()->find($categoryId);
    }

    /**
     * @param Category $category
     *
     * @return Carbon|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function firstUseDate(Category $category): ?Carbon
    {
        $firstJournalDate     = $this->getFirstJournalDate($category);
        $firstTransactionDate = $this->getFirstTransactionDate($category);

        if (null === $firstTransactionDate && null === $firstJournalDate) {
            return null;
        }
        if (null === $firstTransactionDate) {
            return $firstJournalDate;
        }
        if (null === $firstJournalDate) {
            return $firstTransactionDate;
        }

        if ($firstTransactionDate < $firstJournalDate) {
            return $firstTransactionDate;
        }

        return $firstJournalDate;
    }

    /**
     * Get all categories with ID's.
     *
     * @param array $categoryIds
     *
     * @return Collection
     */
    public function getByIds(array $categoryIds): Collection
    {
        return $this->user->categories()->whereIn('id', $categoryIds)->get();
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

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function lastUseDate(Category $category, Collection $accounts): ?Carbon
    {
        $lastJournalDate     = $this->getLastJournalDate($category, $accounts);
        $lastTransactionDate = $this->getLastTransactionDate($category, $accounts);

        if (null === $lastTransactionDate && null === $lastJournalDate) {
            return null;
        }
        if (null === $lastTransactionDate) {
            return $lastJournalDate;
        }
        if (null === $lastJournalDate) {
            return $lastTransactionDate;
        }

        if ($lastTransactionDate < $lastJournalDate) {
            return $lastTransactionDate;
        }

        return $lastJournalDate;
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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getTransactions();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (1 === bccomp($transaction->transaction_amount, '0')) {
                continue;
            }
            $categoryId                          = max((int)$transaction->transaction_journal_category_id, (int)$transaction->transaction_category_id);
            $date                                = $transaction->date->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $transactions = $collector->getTransactions();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_category'),
            'sum'     => '0',
        ];

        foreach ($transactions as $transaction) {
            // if positive, skip:
            if (1 === bccomp($transaction->transaction_amount, '0')) {
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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getTransactions();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // if negative, skip:
            if (bccomp($transaction->transaction_amount, '0') === -1) {
                continue;
            }
            $categoryId                          = max((int)$transaction->transaction_journal_category_id, (int)$transaction->transaction_category_id);
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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->withOpposingAccount();
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $transactions = $collector->getTransactions();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_category'),
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

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
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
        $set = $this->spentInPeriodCollection($categories, $accounts, $start, $end);


        return (string)$set->sum('transaction_amount');
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function spentInPeriodCollection(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCategories($categories);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        return $collector->getTransactions();
    }

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCategory();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId          = $transaction->transaction_currency_id;
            $return[$currencyId] = $return[$currencyId] ?? '0';
            $return[$currencyId] = bcadd($return[$currencyId], $transaction->transaction_amount);
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCategories($categories);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set    = $collector->getTransactions();
        $return = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId          = $transaction->transaction_currency_id;
            $return[$currencyId] = $return[$currencyId] ?? '0';
            $return[$currencyId] = bcadd($return[$currencyId], $transaction->transaction_amount);
        }

        return $return;
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
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCategory();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getTransactions();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        return (string)$set->sum('transaction_amount');
    }

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data): Category
    {
        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate(null, $data['name']);
    }

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data): Category
    {
        /** @var CategoryUpdateService $service */
        $service = app(CategoryUpdateService::class);

        return $service->update($category, $data);
    }

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    private function getFirstJournalDate(Category $category): ?Carbon
    {
        $query  = $category->transactionJournals()->orderBy('date', 'ASC');
        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    private function getFirstTransactionDate(Category $category): ?Carbon
    {
        // check transactions:
        $query = $category->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'ASC');

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    private function getLastJournalDate(Category $category, Collection $accounts): ?Carbon
    {
        $query = $category->transactionJournals()->orderBy('date', 'DESC');

        if ($accounts->count() > 0) {
            $query->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('t.account_id', $accounts->pluck('id')->toArray());
        }

        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    private function getLastTransactionDate(Category $category, Collection $accounts): ?Carbon
    {
        // check transactions:
        $query = $category->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'DESC');
        if ($accounts->count() > 0) {
            // filter journals:
            $query->whereIn('transactions.account_id', $accounts->pluck('id')->toArray());
        }

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }
}
