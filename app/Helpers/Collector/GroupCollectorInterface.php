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
use FireflyIII\Models\UserGroup;
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
     */
    public function amountIs(string $amount): self;

    public function amountIsNot(string $amount): self;

    /**
     * Get transactions where the amount is less than.
     */
    public function amountLess(string $amount): self;

    /**
     * Get transactions where the foreign amount is more than.
     */
    public function amountMore(string $amount): self;

    public function attachmentNameContains(string $name): self;

    public function attachmentNameDoesNotContain(string $name): self;

    public function attachmentNameDoesNotEnd(string $name): self;

    public function attachmentNameDoesNotStart(string $name): self;

    public function attachmentNameEnds(string $name): self;

    public function attachmentNameIs(string $name): self;

    public function attachmentNameIsNot(string $name): self;

    public function attachmentNameStarts(string $name): self;

    public function attachmentNotesAre(string $value): self;

    public function attachmentNotesAreNot(string $value): self;

    public function attachmentNotesContains(string $value): self;

    public function attachmentNotesDoNotContain(string $value): self;

    public function attachmentNotesDoNotEnd(string $value): self;

    public function attachmentNotesDoNotStart(string $value): self;

    public function attachmentNotesEnds(string $value): self;

    public function attachmentNotesStarts(string $value): self;

    public function dayAfter(string $day): self;

    public function dayBefore(string $day): self;

    public function dayIs(string $day): self;

    public function dayIsNot(string $day): self;

    /**
     * End of the description must not match:
     */
    public function descriptionDoesNotEnd(array $array): self;

    /**
     * Beginning of the description must not start with:
     */
    public function descriptionDoesNotStart(array $array): self;

    /**
     * End of the description must match:
     */
    public function descriptionEnds(array $array): self;

    /**
     * Description must be:
     */
    public function descriptionIs(string $value): self;

    /**
     * Description must not be:
     */
    public function descriptionIsNot(string $value): self;

    /**
     * Beginning of the description must match:
     */
    public function descriptionStarts(array $array): self;

    /**
     * These accounts must not be accounts.
     */
    public function excludeAccounts(Collection $accounts): self;

    /**
     * Exclude a specific set of bills
     */
    public function excludeBills(Collection $bills): self;

    /**
     * Exclude a budget
     */
    public function excludeBudget(Budget $budget): self;

    /**
     * Exclude a budget.
     */
    public function excludeBudgets(Collection $budgets): self;

    /**
     * Exclude a set of categories.
     */
    public function excludeCategories(Collection $categories): self;

    /**
     * Exclude a specific category
     */
    public function excludeCategory(Category $category): self;

    /**
     * Limit results to NOT a specific currency, either foreign or normal one.
     */
    public function excludeCurrency(TransactionCurrency $currency): self;

    /**
     * Exclude destination accounts.
     */
    public function excludeDestinationAccounts(Collection $accounts): self;

    /**
     * Look for specific external ID's.
     */
    public function excludeExternalId(string $externalId): self;

    public function excludeExternalUrl(string $url): self;

    /**
     * Limit results to exclude a specific foreign currency.
     */
    public function excludeForeignCurrency(TransactionCurrency $currency): self;

    /**
     * Limit the result to NOT a set of specific transaction groups.
     */
    public function excludeIds(array $groupIds): self;

    /**
     * Look for specific external ID's.
     */
    public function excludeInternalReference(string $internalReference): self;

    /**
     * Limit the result to NOT a set of specific transaction journals.
     */
    public function excludeJournalIds(array $journalIds): self;

    public function excludeMetaDateRange(Carbon $start, Carbon $end, string $field): self;

    public function excludeObjectRange(Carbon $start, Carbon $end, string $field): self;

    public function excludeRange(Carbon $start, Carbon $end): self;

    public function excludeRecurrenceId(string $recurringId): self;

    /**
     * Exclude words in descriptions.
     */
    public function excludeSearchWords(array $array): self;

    /**
     * These accounts must not be source accounts.
     */
    public function excludeSourceAccounts(Collection $accounts): self;

    /**
     * Limit the included transaction types.
     */
    public function excludeTypes(array $types): self;

    public function exists(): self;

    public function externalIdContains(string $externalId): self;

    public function externalIdDoesNotContain(string $externalId): self;

    public function externalIdDoesNotEnd(string $externalId): self;

    public function externalIdDoesNotStart(string $externalId): self;

    public function externalIdEnds(string $externalId): self;

    public function externalIdStarts(string $externalId): self;

    public function externalUrlContains(string $url): self;

    public function externalUrlDoesNotContain(string $url): self;

    public function externalUrlDoesNotEnd(string $url): self;

    public function externalUrlDoesNotStart(string $url): self;

    public function externalUrlEnds(string $url): self;

    public function externalUrlStarts(string $url): self;

    /**
     * Ensure the search will find nothing at all, zero results.
     */
    public function findNothing(): self;

    /**
     * Get transactions with a specific foreign amount.
     */
    public function foreignAmountIs(string $amount): self;

    /**
     * Get transactions with a specific foreign amount.
     */
    public function foreignAmountIsNot(string $amount): self;

    /**
     * Get transactions where the amount is less than.
     */
    public function foreignAmountLess(string $amount): self;

    /**
     * Get transactions where the foreign amount is more than.
     */
    public function foreignAmountMore(string $amount): self;

    public function getExpandGroupSearch(): bool;

    /**
     * Return the transaction journals without group information. Is useful in some instances.
     */
    public function getExtractedJournals(): array;

    /**
     * Return the groups.
     */
    public function getGroups(): Collection;

    /**
     * Same as getGroups but everything is in a paginator.
     */
    public function getPaginatedGroups(): LengthAwarePaginator;

    public function hasAnyTag(): self;

    /**
     * Has attachments
     */
    public function hasAttachments(): self;

    /**
     * Has no attachments
     */
    public function hasNoAttachments(): self;

    public function internalReferenceContains(string $internalReference): self;

    public function internalReferenceDoesNotContain(string $internalReference): self;

    public function internalReferenceDoesNotEnd(string $internalReference): self;

    public function internalReferenceDoesNotStart(string $internalReference): self;

    public function internalReferenceEnds(string $internalReference): self;

    public function internalReferenceStarts(string $internalReference): self;

    /**
     * Only journals that are reconciled.
     */
    public function isNotReconciled(): self;

    /**
     * Only journals that are reconciled.
     */
    public function isReconciled(): self;

    public function metaDayAfter(string $day, string $field): self;

    public function metaDayBefore(string $day, string $field): self;

    public function metaDayIs(string $day, string $field): self;

    public function metaDayIsNot(string $day, string $field): self;

    public function metaMonthAfter(string $month, string $field): self;

    public function metaMonthBefore(string $month, string $field): self;

    public function metaMonthIs(string $month, string $field): self;

    public function metaMonthIsNot(string $month, string $field): self;

    public function metaYearAfter(string $year, string $field): self;

    public function metaYearBefore(string $year, string $field): self;

    public function metaYearIs(string $year, string $field): self;

    public function metaYearIsNot(string $year, string $field): self;

    public function monthAfter(string $month): self;

    public function monthBefore(string $month): self;

    public function monthIs(string $month): self;

    public function monthIsNot(string $month): self;

    public function notesContain(string $value): self;

    public function notesDoNotContain(string $value): self;

    public function notesDontEndWith(string $value): self;

    public function notesDontStartWith(string $value): self;

    public function notesEndWith(string $value): self;

    public function notesExactly(string $value): self;

    public function notesExactlyNot(string $value): self;

    public function notesStartWith(string $value): self;

    public function objectDayAfter(string $day, string $field): self;

    public function objectDayBefore(string $day, string $field): self;

    public function objectDayIs(string $day, string $field): self;

    public function objectDayIsNot(string $day, string $field): self;

    public function objectMonthAfter(string $month, string $field): self;

    public function objectMonthBefore(string $month, string $field): self;

    public function objectMonthIs(string $month, string $field): self;

    public function objectMonthIsNot(string $month, string $field): self;

    public function objectYearAfter(string $year, string $field): self;

    public function objectYearBefore(string $year, string $field): self;

    public function objectYearIs(string $year, string $field): self;

    public function objectYearIsNot(string $year, string $field): self;

    /**
     * Define which accounts can be part of the source and destination transactions.
     */
    public function setAccounts(Collection $accounts): self;

    /**
     * Collect transactions after a specific date.
     */
    public function setAfter(Carbon $date): self;

    /**
     * Limit results to a SPECIFIC set of tags.
     */
    public function setAllTags(Collection $tags): self;

    /**
     * Collect transactions before a specific date.
     */
    public function setBefore(Carbon $date): self;

    /**
     * Limit the search to a specific bill.
     */
    public function setBill(Bill $bill): self;

    /**
     * Limit the search to a specific set of bills.
     */
    public function setBills(Collection $bills): self;

    /**
     * Both source AND destination must be in this list of accounts.
     */
    public function setBothAccounts(Collection $accounts): self;

    /**
     * Limit the search to a specific budget.
     */
    public function setBudget(Budget $budget): self;

    /**
     * Limit the search to a specific set of budgets.
     */
    public function setBudgets(Collection $budgets): self;

    /**
     * Limit the search to a specific bunch of categories.
     */
    public function setCategories(Collection $categories): self;

    /**
     * Limit the search to a specific category.
     */
    public function setCategory(Category $category): self;

    /**
     * Collect transactions created on a specific date.
     */
    public function setCreatedAt(Carbon $date): self;

    /**
     * Limit results to a specific currency, either foreign or normal one.
     */
    public function setCurrency(TransactionCurrency $currency): self;

    /**
     * Limit results to a specific currency, either foreign or normal one.
     */
    public function setNormalCurrency(TransactionCurrency $currency): self;

    /**
     * Set destination accounts.
     */
    public function setDestinationAccounts(Collection $accounts): self;

    /**
     * Set the end time of the results to return.
     */
    public function setEnd(Carbon $end): self;

    /**
     * Set the page to get.
     */
    public function setEndRow(int $endRow): self;

    public function setExpandGroupSearch(bool $expandGroupSearch): self;

    /**
     * Look for specific external ID's.
     */
    public function setExternalId(string $externalId): self;

    public function setExternalUrl(string $url): self;

    /**
     * Limit results to a specific foreign currency.
     */
    public function setForeignCurrency(TransactionCurrency $currency): self;

    /**
     * Limit the result to a set of specific transaction groups.
     */
    public function setIds(array $groupIds): self;

    /**
     * Look for specific external ID's.
     */
    public function setInternalReference(string $internalReference): self;

    /**
     * Limit the result to a set of specific transaction journals.
     */
    public function setJournalIds(array $journalIds): self;

    /**
     * Limit the number of returned entries.
     */
    public function setLimit(int $limit): self;

    /**
     * Collect transactions after a specific date.
     */
    public function setMetaAfter(Carbon $date, string $field): self;

    /**
     * Collect transactions before a specific date.
     */
    public function setMetaBefore(Carbon $date, string $field): self;

    /**
     * Set the start and end time of the results to return, based on meta data.
     */
    public function setMetaDateRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * Define which accounts can NOT be part of the source and destination transactions.
     */
    public function setNotAccounts(Collection $accounts): self;

    public function setObjectAfter(Carbon $date, string $field): self;

    public function setObjectBefore(Carbon $date, string $field): self;

    public function setObjectRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * Set the page to get.
     */
    public function setPage(int $page): self;

    /**
     * Set the start and end time of the results to return.
     */
    public function setRange(Carbon $start, Carbon $end): self;

    /**
     * Look for specific recurring ID's.
     */
    public function setRecurrenceId(string $recurringId): self;

    /**
     * Search for words in descriptions.
     */
    public function setSearchWords(array $array): self;

    public function setSepaCT(string $sepaCT): self;

    public function setSorting(array $instructions): self;

    /**
     * Set source accounts.
     */
    public function setSourceAccounts(Collection $accounts): self;

    /**
     * Set the start time of the results to return.
     */
    public function setStart(Carbon $start): self;

    /**
     * Set the page to get.
     */
    public function setStartRow(int $startRow): self;

    /**
     * Limit results to a specific tag.
     */
    public function setTag(Tag $tag): self;

    /**
     * Limit results to any of the specified tags.
     */
    public function setTags(Collection $tags): self;

    /**
     * Limit the search to one specific transaction group.
     */
    public function setTransactionGroup(TransactionGroup $transactionGroup): self;

    /**
     * Limit the included transaction types.
     */
    public function setTypes(array $types): self;

    /**
     * Collect transactions updated on a specific date.
     */
    public function setUpdatedAt(Carbon $date): self;

    /**
     * Set the user object and start the query.
     */
    public function setUser(User $user): self;

    /**
     * Set the user group object and start the query.
     */
    public function setUserGroup(UserGroup $userGroup): self;

    /**
     * Only when does not have these tags
     */
    public function setWithoutSpecificTags(Collection $tags): self;

    /**
     * Either account can be set, but NOT both. This effectively excludes internal transfers.
     */
    public function setXorAccounts(Collection $accounts): self;

    /**
     * Sort the collection on a column.
     */
    public function sortCollection(Collection $collection): Collection;

    /**
     * Automatically include all stuff required to make API calls work.
     */
    public function withAPIInformation(): self;

    /**
     * Will include the source and destination account names and types.
     */
    public function withAccountInformation(): self;

    /**
     * Any notes, no matter what.
     */
    public function withAnyNotes(): self;

    /**
     * Add basic info on attachments of transactions.
     */
    public function withAttachmentInformation(): self;

    /**
     * Limit results to transactions without a bill..
     */
    public function withBill(): self;

    /**
     * Include bill name + ID.
     */
    public function withBillInformation(): self;

    /**
     * Limit results to a transactions with a budget.
     */
    public function withBudget(): self;

    /**
     * Will include budget ID + name, if any.
     */
    public function withBudgetInformation(): self;

    /**
     * Limit results to a transactions with a category.
     */
    public function withCategory(): self;

    /**
     * Will include category ID + name, if any.
     */
    public function withCategoryInformation(): self;

    /**
     * Transactions with any external ID
     */
    public function withExternalId(): self;

    /**
     * Transactions with any external URL
     */
    public function withExternalUrl(): self;

    /**
     * Transaction must have meta date field X.
     */
    public function withMetaDate(string $field): self;

    /**
     * Will include notes.
     */
    public function withNotes(): self;

    /**
     * Add tag info.
     */
    public function withTagInformation(): self;

    /**
     * Limit results to a transactions without a bill.
     */
    public function withoutBill(): self;

    /**
     * Limit results to a transactions without a budget.
     */
    public function withoutBudget(): self;

    /**
     * Limit results to a transactions without a category.
     */
    public function withoutCategory(): self;

    /**
     * Transactions without an external ID
     */
    public function withoutExternalId(): self;

    /**
     * Transactions without an external URL
     */
    public function withoutExternalUrl(): self;

    public function withoutNotes(): self;

    public function withoutTags(): self;

    public function yearAfter(string $year): self;

    public function yearBefore(string $year): self;

    public function yearIs(string $year): self;

    public function yearIsNot(string $year): self;

    public function accountBalanceIs(string $direction, string $operator, string $value): self;
}
