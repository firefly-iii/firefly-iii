<?php
/**
 * JournalServiceTrait.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\BudgetFactory;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Trait JournalServiceTrait
 *
 */
trait JournalServiceTrait
{

    /**
     * Link tags to journal.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function connectTags(TransactionJournal $journal, array $data): void
    {
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($journal->user);
        $set = [];
        if (!\is_array($data['tags'])) {
            return; // @codeCoverageIgnore
        }
        foreach ($data['tags'] as $string) {
            if ('' !== $string) {
                $tag = $factory->findOrCreate($string);
                if (null !== $tag) {
                    $set[] = $tag->id;
                }
            }
        }
        $journal->tags()->sync($set);
    }


    /**
     * @param int|null    $budgetId
     * @param null|string $budgetName
     *
     * @return Budget|null
     */
    protected function findBudget(?int $budgetId, ?string $budgetName): ?Budget
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user);

        return $factory->find($budgetId, $budgetName);
    }

    /**
     * @param int|null    $categoryId
     * @param null|string $categoryName
     *
     * @return Category|null
     */
    protected function findCategory(?int $categoryId, ?string $categoryName): ?Category
    {
        Log::debug(sprintf('Going to find or create category #%d, with name "%s"', $categoryId, $categoryName));
        /** @var CategoryFactory $factory */
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate($categoryId, $categoryName);
    }


    /**
     * @param TransactionJournal $journal
     * @param Budget|null        $budget
     */
    protected function setBudget(TransactionJournal $journal, ?Budget $budget): void
    {
        if (null === $budget) {
            $journal->budgets()->sync([]);

            return;
        }
        $journal->budgets()->sync([$budget->id]);

    }


    /**
     * @param TransactionJournal $journal
     * @param Category|null      $category
     */
    protected function setCategory(TransactionJournal $journal, ?Category $category): void
    {
        if (null === $category) {
            $journal->categories()->sync([]);

            return;
        }
        $journal->categories()->sync([$category->id]);

    }


    /**
     * @param TransactionJournal $journal
     * @param array              $data
     * @param string             $field
     */
    protected function storeMeta(TransactionJournal $journal, array $data, string $field): void
    {
        $set = [
            'journal' => $journal,
            'name'    => $field,
            'data'    => (string)($data[$field] ?? ''),
        ];

        Log::debug(sprintf('Going to store meta-field "%s", with value "%s".', $set['name'], $set['data']));

        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $notes
     */
    protected function storeNote(TransactionJournal $journal, ?string $notes): void
    {
        $notes = (string)$notes;
        if ('' !== $notes) {
            $note = $journal->notes()->first();
            if (null === $note) {
                $note = new Note;
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();

            return;
        }
        $note = $journal->notes()->first();
        if (null !== $note) {
            try {
                $note->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Journal service trait could not delete note: %s', $e->getMessage()));
            }
        }


    }
}
