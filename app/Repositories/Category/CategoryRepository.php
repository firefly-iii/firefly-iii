<?php
/**
 * CategoryRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use DB;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Category;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\RuleAction;
use FireflyIII\Services\Internal\Destroy\CategoryDestroyService;
use FireflyIII\Services\Internal\Update\CategoryUpdateService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CategoryRepository.
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
            die(__METHOD__);
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
     * @param int|null    $categoryId
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
     * @param Category $category
     *
     * @return Carbon|null
     *
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

        return $set;
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon|null
     *
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

    /**
     * Delete all categories.
     */
    public function destroyAll(): void
    {
        $categories = $this->getCategories();
        /** @var Category $category */
        foreach ($categories as $category) {
            DB::table('category_transaction')->where('category_id', $category->id)->delete();
            DB::table('category_transaction_journal')->where('category_id', $category->id)->delete();
            RecurrenceTransactionMeta::where('name', 'category_id')->where('value', $category->id)->delete();
            RuleAction::where('action_type', 'set_category')->where('action_value', $category->name)->delete();
            $category->delete();
        }
    }
}
