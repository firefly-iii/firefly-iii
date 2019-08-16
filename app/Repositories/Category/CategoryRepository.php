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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Category;
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
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
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

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function earnedInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);


        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setCategory($category);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // collect and group results:
        $array  = $collector->getExtractedJournals();
        $return = [];

        foreach ($array as $journal) {
            $currencyCode = $journal['currency_code'];
            if (!isset($return[$currencyCode])) {
                $return[$currencyCode] = [
                    'currency_id'             => $journal['currency_id'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'earned'                  => '0',
                ];
            }

            // also extract foreign currency information:
            if (null !== $journal['foreign_currency_id']) {
                $currencyCode = $journal['foreign_currency_code'];
                if (!isset($return[$currencyCode])) {
                    $return[$currencyCode] = [
                        'currency_id'             => $journal['foreign_currency_id'],
                        'currency_code'           => $journal['foreign_currency_code'],
                        'currency_name'           => $journal['foreign_currency_name'],
                        'currency_symbol'         => $journal['foreign_currency_symbol'],
                        'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                        'earned'                  => '0',
                    ];
                }
                $return[$currencyCode]['earned'] = bcadd($return[$currencyCode]['earned'], app('steam')->positive($journal['foreign_amount']));
            }
            $return[$currencyCode]['earned'] = bcadd($return[$currencyCode]['earned'], app('steam')->positive($journal['amount']));
        }

        return $return;
    }

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount earned in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function earnedInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->withoutCategory();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals = $collector->getExtractedJournals();
        $return   = [];

        foreach ($journals as $journal) {
            $currencyId = (int)$journal['currency_id'];
            if (!isset($return[$currencyId])) {
                $return[$currencyId] = [
                    'earned'                  => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$currencyId]['earned'] = bcadd($return[$currencyId]['earned'], $journal['amount']);
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function earnedInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);

        if ($categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (0 === $categories->count()) {
            $collector->setCategories($this->getCategories());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals = $collector->getExtractedJournals();
        $return   = [];
        foreach ($journals as $journal) {
            $categoryId = (int)$journal['category_id'];
            $currencyId = (int)$journal['currency_id'];
            $name       = $journal['category_name'];
            // make array for category:
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = [
                    'name'   => $name,
                    'earned' => [],
                ];
            }
            if (!isset($return[$categoryId]['earned'][$currencyId])) {
                $return[$categoryId]['earned'][$currencyId] = [
                    'earned'                  => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$categoryId]['earned'][$currencyId]['earned']
                = bcadd($return[$categoryId]['earned'][$currencyId]['earned'], $journal['amount']);
        }

        return $return;
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

        return $set;
    }

    /**
     * @param int|null $categoryId
     * @param string|null $categoryName
     *
     * @return Category|null
     */
    public function findCategory(?int $categoryId, ?string $categoryName): ?Category
    {
        Log::debug('Now in findCategory()');
        Log::debug(sprintf('Searching for category with ID #%d...', $categoryId));
        $result = $this->findNull((int)$categoryId);
        if (null === $result) {
            Log::debug(sprintf('Searching for category with name %s...', $categoryName));
            $result = $this->findByName((string)$categoryName);
            if (null === $result && '' !== (string)$categoryName) {
                // create it!
                $result = $this->store(['name' => $categoryName]);
            }
        }
        if (null !== $result) {
            Log::debug(sprintf('Found category #%d: %s', $result->id, $result->name));
        }
        Log::debug(sprintf('Found category result is null? %s', var_export(null === $result, true)));

        return $result;
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
     * Find a category.
     *
     * @param string $name
     *
     * @return Category|null
     */
    public function findByName(string $name): ?Category
    {
        $categories = $this->user->categories()->get(['categories.*']);

        // TODO no longer need to loop like this

        foreach ($categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return null;
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
     * @param Category $category
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

        if ($lastTransactionDate > $lastJournalDate) {
            return $lastTransactionDate;
        }

        return $lastJournalDate;
    }



    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
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
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withAccountInformation()->withCategoryInformation();
        $journals = $collector->getExtractedJournals();

        // loop transactions:

        foreach ($journals as $journal) {
            $categoryId                          = (int)$journal['category_id'];
            $date                                = $journal['date']->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $journal['amount']);
        }

        return $data;
    }



    /**
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function periodExpensesNoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end)->withAccountInformation();
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $journals = $collector->getExtractedJournals();
        $result   = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_category'),
            'sum'     => '0',
        ];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $date = $journal['date']->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $journal['amount']);
        }

        return $result;
    }



    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
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
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withAccountInformation();
        $journals = $collector->getExtractedJournals();

        // loop transactions:
        /** @var array $journal */
        foreach ($journals as $journal) {
            $categoryId                          = (int)$journal['category_id'];
            $date                                = $journal['date']->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $journal['amount']);
        }

        return $data;
    }

    /**
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function periodIncomeNoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        Log::debug('Now in periodIncomeNoCategory()');
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end)->withAccountInformation();
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->withoutCategory();
        $journals = $collector->getExtractedJournals();
        $result   = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_category'),
            'sum'     => '0',
        ];
        Log::debug('Looping transactions..');

        foreach ($journals as $journal) {
            $date = $journal['date']->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $journal['amount']);
        }
        Log::debug('Done looping transactions..');
        Log::debug('Finished periodIncomeNoCategory()');

        return $result;
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchCategory(string $query): Collection
    {
        $search = $this->user->categories();
        if ('' !== $query) {
            $search->where('name', 'LIKE', sprintf('%%%s%%', $query));
        }

        return $search->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Returns the amount spent in a category, for a set of accounts, in a specific period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCategory($category);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // collect and group results:
        $array  = $collector->getExtractedJournals();
        $return = [];

        foreach ($array as $journal) {
            $currencyCode = $journal['currency_code'];
            if (!isset($return[$currencyCode])) {
                $return[$currencyCode] = [
                    'currency_id'             => $journal['currency_id'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'spent'                   => '0',
                ];
            }

            // also extract foreign currency information:
            if (null !== $journal['foreign_currency_id']) {
                $currencyCode = $journal['foreign_currency_code'];
                if (!isset($return[$currencyCode])) {
                    $return[$currencyCode] = [
                        'currency_id'             => $journal['foreign_currency_id'],
                        'currency_code'           => $journal['foreign_currency_code'],
                        'currency_name'           => $journal['foreign_currency_name'],
                        'currency_symbol'         => $journal['foreign_currency_symbol'],
                        'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                        'spent'                   => '0',
                    ];
                }
                $return[$currencyCode]['spent'] = bcadd($return[$currencyCode]['spent'], $journal['foreign_amount']);
            }
            $return[$currencyCode]['spent'] = bcadd($return[$currencyCode]['spent'], $journal['amount']);
        }

        return $return;
    }

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function spentInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCategory();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        $set    = $collector->getExtractedJournals();
        $return = [];
        /** @var array $journal */
        foreach ($set as $journal) {
            $currencyId = (int)$journal['currency_id'];
            if (!isset($return[$currencyId])) {
                $return[$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$currencyId]['spent'] = bcadd($return[$currencyId]['spent'], $journal['amount']);
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);

        if ($categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (0 === $categories->count()) {
            $collector->setCategories($this->getCategories());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        $set    = $collector->getExtractedJournals();
        $return = [];
        /** @var array $journal */
        foreach ($set as $journal) {
            $categoryId = (int)$journal['category_id'];
            $currencyId = (int)$journal['currency_id'];
            $name       = $journal['category_name'];

            // make array for category:
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = [
                    'name'  => $name,
                    'spent' => [],
                ];
            }
            if (!isset($return[$categoryId]['spent'][$currencyId])) {
                $return[$categoryId]['spent'][$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$categoryId]['spent'][$currencyId]['spent']
                = bcadd($return[$categoryId]['spent'][$currencyId]['spent'], $journal['amount']);
        }

        return $return;
    }

    /**
     * @param Category $category
     * @param array $data
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
     * TODO does not take currencies into account.
     *
     * @param array $journals
     *
     * @return string
     */
    private function sumJournals(array $journals): string
    {
        $sum = '0';
        /** @var array $journal */
        foreach ($journals as $journal) {
            $amount = (string)$journal['amount'];
            $sum    = bcadd($sum, $amount);
        }

        return $sum;
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
     * @param Category $category
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
     * @param Category $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     * @throws \Exception
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
