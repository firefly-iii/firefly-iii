<?php

/**
 * CategoryRepository.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\RuleAction;
use FireflyIII\Services\Internal\Destroy\CategoryDestroyService;
use FireflyIII\Services\Internal\Update\CategoryUpdateService;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class CategoryRepository.
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    use UserGroupTrait;

    public function categoryEndsWith(string $query, int $limit): Collection
    {
        $search = $this->user->categories();
        if ('' !== $query) {
            $search->whereLike('name', sprintf('%%%s', $query));
        }

        return $search->take($limit)->get();
    }

    public function categoryStartsWith(string $query, int $limit): Collection
    {
        $search = $this->user->categories();
        if ('' !== $query) {
            $search->whereLike('name', sprintf('%s%%', $query));
        }

        return $search->take($limit)->get();
    }

    public function destroy(Category $category): bool
    {
        /** @var CategoryDestroyService $service */
        $service = app(CategoryDestroyService::class);
        $service->destroy($category);

        return true;
    }

    /**
     * Delete all categories.
     */
    public function destroyAll(): void
    {
        $categories = $this->getCategories();

        /** @var Category $category */
        foreach ($categories as $category) {
            \DB::table('category_transaction')->where('category_id', $category->id)->delete();
            \DB::table('category_transaction_journal')->where('category_id', $category->id)->delete();
            RecurrenceTransactionMeta::where('name', 'category_id')->where('value', $category->id)->delete();
            RuleAction::where('action_type', 'set_category')->where('action_value', $category->name)->delete();
            $category->delete();
        }
        Log::channel('audit')->info('Delete all categories through destroyAll');
    }

    /**
     * Returns a list of all the categories belonging to a user.
     */
    public function getCategories(): Collection
    {
        return $this->user->categories()->with(['attachments'])->orderBy('name', 'ASC')->get();
    }

    /**
     * @throws FireflyException
     */
    public function findCategory(?int $categoryId, ?string $categoryName): ?Category
    {
        app('log')->debug('Now in findCategory()');
        app('log')->debug(sprintf('Searching for category with ID #%d...', $categoryId));
        $result = $this->find((int) $categoryId);
        if (null === $result) {
            app('log')->debug(sprintf('Searching for category with name %s...', $categoryName));
            $result = $this->findByName((string) $categoryName);
            if (null === $result && '' !== (string) $categoryName) {
                // create it!
                $result = $this->store(['name' => $categoryName]);
            }
        }
        if (null !== $result) {
            app('log')->debug(sprintf('Found category #%d: %s', $result->id, $result->name));
        }
        app('log')->debug(sprintf('Found category result is null? %s', var_export(null === $result, true)));

        return $result;
    }

    /**
     * Find a category or return NULL
     */
    public function find(int $categoryId): ?Category
    {
        /** @var null|Category */
        return $this->user->categories()->find($categoryId);
    }

    /**
     * Find a category.
     */
    public function findByName(string $name): ?Category
    {
        /** @var null|Category */
        return $this->user->categories()->where('name', $name)->first(['categories.*']);
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Category
    {
        /** @var CategoryFactory $factory */
        $factory  = app(CategoryFactory::class);
        $factory->setUser($this->user);

        $category = $factory->findOrCreate(null, $data['name']);

        if (null === $category) {
            throw new FireflyException(sprintf('400003: Could not store new category with name "%s"', $data['name']));
        }

        if (array_key_exists('notes', $data) && '' === $data['notes']) {
            $this->removeNotes($category);
        }
        if (array_key_exists('notes', $data) && '' !== $data['notes']) {
            $this->updateNotes($category, $data['notes']);
        }

        return $category;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function removeNotes(Category $category): void
    {
        $category->notes()->delete();
    }

    public function updateNotes(Category $category, string $notes): void
    {
        $dbNote       = $category->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($category);
        }
        $dbNote->text = trim($notes);
        $dbNote->save();
    }

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

    private function getFirstJournalDate(Category $category): ?Carbon
    {
        $query  = $category->transactionJournals()->orderBy('date', 'ASC');
        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    private function getFirstTransactionDate(Category $category): ?Carbon
    {
        // check transactions:
        $query           = $category->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'ASC')
        ;

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }

    public function getAttachments(Category $category): Collection
    {
        $set  = $category->attachments()->get();

        /** @var \Storage $disk */
        $disk = \Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) { // @phpstan-ignore-line
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes_text  = null !== $notes ? $notes->text : '';

                return $attachment;
            }
        );
    }

    /**
     * Get all categories with ID's.
     */
    public function getByIds(array $categoryIds): Collection
    {
        return $this->user->categories()->whereIn('id', $categoryIds)->get();
    }

    public function getNoteText(Category $category): ?string
    {
        $dbNote = $category->notes()->first();
        if (null === $dbNote) {
            return null;
        }

        return $dbNote->text;
    }

    /**
     * @throws \Exception
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

    private function getLastJournalDate(Category $category, Collection $accounts): ?Carbon
    {
        $query  = $category->transactionJournals()->orderBy('date', 'DESC');

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
     * @throws \Exception
     */
    private function getLastTransactionDate(Category $category, Collection $accounts): ?Carbon
    {
        // check transactions:
        $query           = $category->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'DESC')
        ;
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

    public function searchCategory(string $query, int $limit): Collection
    {
        $search = $this->user->categories();
        if ('' !== $query) {
            $search->whereLike('name', sprintf('%%%s%%', $query));
        }

        return $search->take($limit)->get();
    }

    /**
     * @throws \Exception
     */
    public function update(Category $category, array $data): Category
    {
        /** @var CategoryUpdateService $service */
        $service = app(CategoryUpdateService::class);
        $service->setUser($this->user);

        return $service->update($category, $data);
    }
}
