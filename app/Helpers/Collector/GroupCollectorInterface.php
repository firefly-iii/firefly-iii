<?php
/**
 * GroupCollectorInterface.php
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

namespace FireflyIII\Helpers\Collector;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface GroupCollectorInterface
 */
interface GroupCollectorInterface
{
    /**
     * Get transactions with a specific amount.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountIs(string $amount): GroupCollectorInterface;

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountLess(string $amount): GroupCollectorInterface;

    /**
     * Get transactions where the amount is more than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountMore(string $amount): GroupCollectorInterface;

    /**
     * Exclude destination accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function excludeDestinationAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * These accounts must not be source accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function excludeSourceAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Return the transaction journals without group information. Is useful in some instances.
     *
     * @return array
     */
    public function getExtractedJournals(): array;

    /**
     * Return the groups.
     *
     * @return Collection
     */
    public function getGroups(): Collection;

    /**
     * Same as getGroups but everything is in a paginator.
     *
     * @return LengthAwarePaginator
     */
    public function getPaginatedGroups(): LengthAwarePaginator;

    /**
     * Define which accounts can be part of the source and destination transactions.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Collect transactions after a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setAfter(Carbon $date): GroupCollectorInterface;

    /**
     * Collect transactions before a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setBefore(Carbon $date): GroupCollectorInterface;

    /**
     * Limit the search to a specific bill.
     *
     * @param Bill $bill
     *
     * @return GroupCollectorInterface
     */
    public function setBill(Bill $bill): GroupCollectorInterface;

    /**
     * Limit the search to a specific set of bills.
     *
     * @param Collection $bills
     *
     * @return GroupCollectorInterface
     */
    public function setBills(Collection $bills): GroupCollectorInterface;

    /**
     * Both source AND destination must be in this list of accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setBothAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Limit the search to a specific budget.
     *
     * @param Budget $budget
     *
     * @return GroupCollectorInterface
     */
    public function setBudget(Budget $budget): GroupCollectorInterface;

    /**
     * Limit the search to a specific set of budgets.
     *
     * @param Collection $budgets
     *
     * @return GroupCollectorInterface
     */
    public function setBudgets(Collection $budgets): GroupCollectorInterface;

    /**
     * Limit the search to a specific bunch of categories.
     *
     * @param Collection $categories
     *
     * @return GroupCollectorInterface
     */
    public function setCategories(Collection $categories): GroupCollectorInterface;

    /**
     * Limit the search to a specific category.
     *
     * @param Category $category
     *
     * @return GroupCollectorInterface
     */
    public function setCategory(Category $category): GroupCollectorInterface;

    /**
     * Collect transactions created on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setCreatedAt(Carbon $date): GroupCollectorInterface;

    /**
     * Limit results to a specific currency, either foreign or normal one.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function setCurrency(TransactionCurrency $currency): GroupCollectorInterface;

    /**
     * Limit results to a specific foreign currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function setForeignCurrency(TransactionCurrency $currency): GroupCollectorInterface;

    /**
     * Set destination accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setDestinationAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Limit the result to a set of specific transaction journals.
     *
     * @param array $journalIds
     *
     * @return GroupCollectorInterface
     */
    public function setJournalIds(array $journalIds): GroupCollectorInterface;

    /**
     * Limit the result to a set of specific transaction groups.
     *
     * @param array $groupIds
     *
     * @return GroupCollectorInterface
     */
    public function setIds(array $groupIds): GroupCollectorInterface;

    /**
     * Limit the number of returned entries.
     *
     * @param int $limit
     *
     * @return GroupCollectorInterface
     */
    public function setLimit(int $limit): GroupCollectorInterface;

    /**
     * Set the page to get.
     *
     * @param int $page
     *
     * @return GroupCollectorInterface
     */
    public function setPage(int $page): GroupCollectorInterface;

    /**
     * Set the start and end time of the results to return.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): GroupCollectorInterface;

    /**
     * Search for words in descriptions.
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function setSearchWords(array $array): GroupCollectorInterface;

    /**
     * Beginning of the description must match:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionStarts(array $array): GroupCollectorInterface;

    /**
     * End of the description must match:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionEnds(array $array): GroupCollectorInterface;

    /**
     * Description must be:
     *
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function descriptionIs(string $value): GroupCollectorInterface;

    /**
     * Set source accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setSourceAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Limit results to a specific tag.
     *
     * @param Tag $tag
     *
     * @return GroupCollectorInterface
     */
    public function setTag(Tag $tag): GroupCollectorInterface;

    /**
     * Limit results to a specific set of tags.
     *
     * @param Collection $tags
     *
     * @return GroupCollectorInterface
     */
    public function setTags(Collection $tags): GroupCollectorInterface;

    /**
     * @return GroupCollectorInterface
     */
    public function withoutTags(): GroupCollectorInterface;

    /**
     * @return GroupCollectorInterface
     */
    public function hasAnyTag(): GroupCollectorInterface;

    /**
     * Limit the search to one specific transaction group.
     *
     * @param TransactionGroup $transactionGroup
     *
     * @return GroupCollectorInterface
     */
    public function setTransactionGroup(TransactionGroup $transactionGroup): GroupCollectorInterface;

    /**
     * Limit the included transaction types.
     *
     * @param array $types
     *
     * @return GroupCollectorInterface
     */
    public function setTypes(array $types): GroupCollectorInterface;

    /**
     * Collect transactions updated on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setUpdatedAt(Carbon $date): GroupCollectorInterface;

    /**
     * Set the user object and start the query.
     *
     * @param User $user
     *
     * @return GroupCollectorInterface
     */
    public function setUser(User $user): GroupCollectorInterface;

    /**
     * Either account can be set, but NOT both. This effectively excludes internal transfers.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setXorAccounts(Collection $accounts): GroupCollectorInterface;

    /**
     * Automatically include all stuff required to make API calls work.
     *
     * @return GroupCollectorInterface
     */
    public function withAPIInformation(): GroupCollectorInterface;

    /**
     * Will include the source and destination account names and types.
     *
     * @return GroupCollectorInterface
     */
    public function withAccountInformation(): GroupCollectorInterface;

    /**
     * Add basic info on attachments of transactions.
     *
     * @return GroupCollectorInterface
     */
    public function withAttachmentInformation(): GroupCollectorInterface;

    /**
     * Has attachments
     *
     * @return GroupCollectorInterface
     */
    public function hasAttachments(): GroupCollectorInterface;

    /**
     * Include bill name + ID.
     *
     * @return GroupCollectorInterface
     */
    public function withBillInformation(): GroupCollectorInterface;

    /**
     * Will include budget ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBudgetInformation(): GroupCollectorInterface;

    /**
     * Will include category ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withCategoryInformation(): GroupCollectorInterface;

    /**
     * Will include notes.
     *
     * @return GroupCollectorInterface
     */
    public function withNotes(): GroupCollectorInterface;

    /**
     * Any notes, no matter what.
     *
     * @return GroupCollectorInterface
     */
    public function withAnyNotes(): GroupCollectorInterface;

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function notesContain(string $value): GroupCollectorInterface;
    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function withoutNotes(): GroupCollectorInterface;

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function notesStartWith(string $value): GroupCollectorInterface;

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function notesEndWith(string $value): GroupCollectorInterface;

    /**
     * @param string $value
     * @return GroupCollectorInterface
     */
    public function notesExactly(string $value): GroupCollectorInterface;

    /**
     * Add tag info.
     *
     * @return GroupCollectorInterface
     */
    public function withTagInformation(): GroupCollectorInterface;

    /**
     * Limit results to a transactions without a budget.
     *
     * @return GroupCollectorInterface
     */
    public function withoutBudget(): GroupCollectorInterface;

    /**
     * Limit results to a transactions without a bill.
     *
     * @return GroupCollectorInterface
     */
    public function withoutBill(): GroupCollectorInterface;

    /**
     * Limit results to a transactions without a category.
     *
     * @return GroupCollectorInterface
     */
    public function withoutCategory(): GroupCollectorInterface;

    /**
     * Limit results to a transactions with a category.
     *
     * @return GroupCollectorInterface
     */
    public function withCategory(): GroupCollectorInterface;

    /**
     * Limit results to a transactions with a budget.
     *
     * @return GroupCollectorInterface
     */
    public function withBudget(): GroupCollectorInterface;

    /**
     * Look for specific external ID's.
     *
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function setExternalId(string $externalId): GroupCollectorInterface;

    /**
     * Look for specific external ID's.
     *
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function setInternalReference(string $externalId): GroupCollectorInterface;

}
