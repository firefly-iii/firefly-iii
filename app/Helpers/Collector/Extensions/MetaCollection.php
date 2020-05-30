<?php
declare(strict_types=1);
/**
 * MetaCollection.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Collector\Extensions;

use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

/**
 * Trait MetaCollection
 */
trait MetaCollection
{


    /**
     * Limit the search to a specific bill.
     *
     * @param Bill $bill
     *
     * @return GroupCollectorInterface
     */
    public function setBill(Bill $bill): GroupCollectorInterface
    {
        $this->withBillInformation();
        $this->query->where('transaction_journals.bill_id', '=', $bill->id);

        return $this;
    }

    /**
     * Limit the search to a specific set of bills.
     *
     * @param Collection $bills
     *
     * @return GroupCollectorInterface
     */
    public function setBills(Collection $bills): GroupCollectorInterface
    {
        $this->withBillInformation();
        $this->query->whereIn('transaction_journals.bill_id', $bills->pluck('id')->toArray());

        return $this;
    }

    /**
     * Limit the search to a specific budget.
     *
     * @param Budget $budget
     *
     * @return GroupCollectorInterface
     */
    public function setBudget(Budget $budget): GroupCollectorInterface
    {
        $this->withBudgetInformation();
        $this->query->where('budgets.id', $budget->id);

        return $this;
    }

    /**
     * Limit the search to a specific set of budgets.
     *
     * @param Collection $budgets
     *
     * @return GroupCollectorInterface
     */
    public function setBudgets(Collection $budgets): GroupCollectorInterface
    {
        if ($budgets->count() > 0) {
            $this->withBudgetInformation();
            $this->query->whereIn('budgets.id', $budgets->pluck('id')->toArray());
        }

        return $this;
    }

    /**
     * Limit the search to a specific bunch of categories.
     *
     * @param Collection $categories
     *
     * @return GroupCollectorInterface
     */
    public function setCategories(Collection $categories): GroupCollectorInterface
    {
        if ($categories->count() > 0) {
            $this->withCategoryInformation();
            $this->query->whereIn('categories.id', $categories->pluck('id')->toArray());
        }

        return $this;
    }

    /**
     * Limit the search to a specific category.
     *
     * @param Category $category
     *
     * @return GroupCollectorInterface
     */
    public function setCategory(Category $category): GroupCollectorInterface
    {
        $this->withCategoryInformation();
        $this->query->where('categories.id', $category->id);

        return $this;
    }

    /**
     * Limit results to a specific tag.
     *
     * @param Tag $tag
     *
     * @return GroupCollectorInterface
     */
    public function setTag(Tag $tag): GroupCollectorInterface
    {
        $this->withTagInformation();
        $this->query->where('tag_transaction_journal.tag_id', $tag->id);

        return $this;
    }

    /**
     * Limit results to a specific set of tags.
     *
     * @param Collection $tags
     *
     * @return GroupCollectorInterface
     */
    public function setTags(Collection $tags): GroupCollectorInterface
    {
        $this->withTagInformation();
        $this->query->whereIn('tag_transaction_journal.tag_id', $tags->pluck('id')->toArray());

        return $this;
    }

    /**
     * Will include bill name + ID, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBillInformation(): GroupCollectorInterface
    {
        if (false === $this->hasBillInformation) {
            // join bill table
            $this->query->leftJoin('bills', 'bills.id', '=', 'transaction_journals.bill_id');
            // add fields
            $this->fields[]           = 'bills.id as bill_id';
            $this->fields[]           = 'bills.name as bill_name';
            $this->hasBillInformation = true;
        }

        return $this;
    }

    /**
     * Will include budget ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBudgetInformation(): GroupCollectorInterface
    {
        if (false === $this->hasBudgetInformation) {
            // join link table
            $this->query->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            // join cat table
            $this->query->leftJoin('budgets', 'budget_transaction_journal.budget_id', '=', 'budgets.id');
            // add fields
            $this->fields[]             = 'budgets.id as budget_id';
            $this->fields[]             = 'budgets.name as budget_name';
            $this->hasBudgetInformation = true;
        }

        return $this;
    }

    /**
     * Will include category ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withCategoryInformation(): GroupCollectorInterface
    {
        if (false === $this->hasCatInformation) {
            // join link table
            $this->query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            // join cat table
            $this->query->leftJoin('categories', 'category_transaction_journal.category_id', '=', 'categories.id');
            // add fields
            $this->fields[]          = 'categories.id as category_id';
            $this->fields[]          = 'categories.name as category_name';
            $this->hasCatInformation = true;
        }

        return $this;
    }

    /**
     * @return GroupCollectorInterface
     */
    public function withTagInformation(): GroupCollectorInterface
    {
        $this->fields[] = 'tags.id as tag_id';
        $this->fields[] = 'tags.tag as tag_name';
        $this->fields[] = 'tags.date as tag_date';
        $this->fields[] = 'tags.description as tag_description';
        $this->fields[] = 'tags.latitude as tag_latitude';
        $this->fields[] = 'tags.longitude as tag_longitude';
        $this->fields[] = 'tags.zoomLevel as tag_zoom_level';

        $this->joinTagTables();

        return $this;
    }

    /**
     * Limit results to a transactions without a budget..
     *
     * @return GroupCollectorInterface
     */
    public function withoutBudget(): GroupCollectorInterface
    {
        $this->withBudgetInformation();
        $this->query->where(
            function (EloquentBuilder $q) {
                $q->whereNull('budget_transaction_journal.budget_id');
            }
        );

        return $this;
    }

    /**
     * Limit results to a transactions without a category.
     *
     * @return GroupCollectorInterface
     */
    public function withoutCategory(): GroupCollectorInterface
    {
        $this->withCategoryInformation();
        $this->query->where(
            function (EloquentBuilder $q) {
                $q->whereNull('category_transaction_journal.category_id');
            }
        );

        return $this;
    }

    /**
     * Join table to get tag information.
     */
    protected function joinTagTables(): void
    {
        if (false === $this->hasJoinedTagTables) {
            // join some extra tables:
            $this->hasJoinedTagTables = true;
            $this->query->leftJoin('tag_transaction_journal', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $this->query->leftJoin('tags', 'tag_transaction_journal.tag_id', '=', 'tags.id');
        }
    }


}
