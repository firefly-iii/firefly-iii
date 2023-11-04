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
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountIs(string $amount): self;

    /**
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountIsNot(string $amount): self;

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountLess(string $amount): self;

    /**
     * Get transactions where the foreign amount is more than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function amountMore(string $amount): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameContains(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameDoesNotContain(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameDoesNotEnd(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameDoesNotStart(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameEnds(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameIs(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameIsNot(string $name): self;

    /**
     * @param string $name
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNameStarts(string $name): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesAre(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesAreNot(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesContains(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesDoNotContain(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesDoNotEnd(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesDoNotStart(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesEnds(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function attachmentNotesStarts(string $value): self;

    /**
     * @param string $day
     *
     * @return GroupCollectorInterface
     */
    public function dayAfter(string $day): self;

    /**
     * @param string $day
     *
     * @return GroupCollectorInterface
     */
    public function dayBefore(string $day): self;

    /**
     * @param string $day
     *
     * @return GroupCollectorInterface
     */
    public function dayIs(string $day): self;

    /**
     * @param string $day
     *
     * @return GroupCollectorInterface
     */
    public function dayIsNot(string $day): self;

    /**
     * End of the description must not match:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionDoesNotEnd(array $array): self;

    /**
     * Beginning of the description must not start with:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionDoesNotStart(array $array): self;

    /**
     * End of the description must match:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionEnds(array $array): self;

    /**
     * Description must be:
     *
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function descriptionIs(string $value): self;

    /**
     * Description must not be:
     *
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function descriptionIsNot(string $value): self;

    /**
     * Beginning of the description must match:
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function descriptionStarts(array $array): self;

    /**
     * These accounts must not be accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function excludeAccounts(Collection $accounts): self;

    /**
     * Exclude a specific set of bills
     *
     * @param Collection $bills
     *
     * @return GroupCollectorInterface
     */
    public function excludeBills(Collection $bills): self;

    /**
     * Exclude a budget
     *
     * @param Budget $budget
     *
     * @return GroupCollectorInterface
     */
    public function excludeBudget(Budget $budget): self;

    /**
     * Exclude a budget.
     *
     * @param Collection $budgets
     *
     * @return GroupCollectorInterface
     */
    public function excludeBudgets(Collection $budgets): self;

    /**
     * Exclude a set of categories.
     *
     * @param Collection $categories
     *
     * @return GroupCollectorInterface
     */
    public function excludeCategories(Collection $categories): self;

    /**
     * Exclude a specific category
     *
     * @param Category $category
     *
     * @return GroupCollectorInterface
     */
    public function excludeCategory(Category $category): self;

    /**
     * Limit results to NOT a specific currency, either foreign or normal one.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function excludeCurrency(TransactionCurrency $currency): self;

    /**
     * Exclude destination accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function excludeDestinationAccounts(Collection $accounts): self;

    /**
     * Look for specific external ID's.
     *
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function excludeExternalId(string $externalId): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function excludeExternalUrl(string $url): self;

    /**
     * Limit results to exclude a specific foreign currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function excludeForeignCurrency(TransactionCurrency $currency): self;

    /**
     * Limit the result to NOT a set of specific transaction groups.
     *
     * @param array $groupIds
     *
     * @return GroupCollectorInterface
     */
    public function excludeIds(array $groupIds): self;

    /**
     * Look for specific external ID's.
     *
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function excludeInternalReference(string $internalReference): self;

    /**
     * Limit the result to NOT a set of specific transaction journals.
     *
     * @param array $journalIds
     *
     * @return GroupCollectorInterface
     */
    public function excludeJournalIds(array $journalIds): self;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function excludeMetaDateRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function excludeObjectRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function excludeRange(Carbon $start, Carbon $end): self;

    /**
     * @param string $recurringId
     *
     * @return GroupCollectorInterface
     */
    public function excludeRecurrenceId(string $recurringId): self;

    /**
     * Exclude words in descriptions.
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function excludeSearchWords(array $array): self;

    /**
     * These accounts must not be source accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function excludeSourceAccounts(Collection $accounts): self;

    /**
     * Limit the included transaction types.
     *
     * @param array $types
     *
     * @return GroupCollectorInterface
     */
    public function excludeTypes(array $types): self;

    /**
     * @return GroupCollectorInterface
     */
    public function exists(): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdContains(string $externalId): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdDoesNotContain(string $externalId): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdDoesNotEnd(string $externalId): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdDoesNotStart(string $externalId): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdEnds(string $externalId): self;

    /**
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function externalIdStarts(string $externalId): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlContains(string $url): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlDoesNotContain(string $url): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlDoesNotEnd(string $url): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlDoesNotStart(string $url): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlEnds(string $url): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function externalUrlStarts(string $url): self;

    /**
     * Ensure the search will find nothing at all, zero results.
     *
     * @return GroupCollectorInterface
     */
    public function findNothing(): self;

    /**
     * Get transactions with a specific foreign amount.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function foreignAmountIs(string $amount): self;

    /**
     * Get transactions with a specific foreign amount.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function foreignAmountIsNot(string $amount): self;

    /**
     * Get transactions where the amount is less than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function foreignAmountLess(string $amount): self;

    /**
     * Get transactions where the foreign amount is more than.
     *
     * @param string $amount
     *
     * @return GroupCollectorInterface
     */
    public function foreignAmountMore(string $amount): self;

    /**
     * @return bool
     */
    public function getExpandGroupSearch(): bool;

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
     * @return GroupCollectorInterface
     */
    public function hasAnyTag(): self;

    /**
     * Has attachments
     *
     * @return GroupCollectorInterface
     */
    public function hasAttachments(): self;

    /**
     * Has no attachments
     *
     * @return GroupCollectorInterface
     */
    public function hasNoAttachments(): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceContains(string $internalReference): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceDoesNotContain(string $internalReference): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceDoesNotEnd(string $internalReference): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceDoesNotStart(string $internalReference): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceEnds(string $internalReference): self;

    /**
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function internalReferenceStarts(string $internalReference): self;

    /**
     * Only journals that are reconciled.
     *
     * @return GroupCollectorInterface
     */
    public function isNotReconciled(): self;

    /**
     * Only journals that are reconciled.
     *
     * @return GroupCollectorInterface
     */
    public function isReconciled(): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaDayAfter(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaDayBefore(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaDayIs(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaDayIsNot(string $day, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaMonthAfter(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaMonthBefore(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaMonthIs(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaMonthIsNot(string $month, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaYearAfter(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaYearBefore(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaYearIs(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function metaYearIsNot(string $year, string $field): self;

    /**
     * @param string $month
     *
     * @return GroupCollectorInterface
     */
    public function monthAfter(string $month): self;

    /**
     * @param string $month
     *
     * @return GroupCollectorInterface
     */
    public function monthBefore(string $month): self;

    /**
     * @param string $month
     *
     * @return GroupCollectorInterface
     */
    public function monthIs(string $month): self;

    /**
     * @param string $month
     *
     * @return GroupCollectorInterface
     */
    public function monthIsNot(string $month): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesContain(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesDoNotContain(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesDontEndWith(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesDontStartWith(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesEndWith(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesExactly(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesExactlyNot(string $value): self;

    /**
     * @param string $value
     *
     * @return GroupCollectorInterface
     */
    public function notesStartWith(string $value): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectDayAfter(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectDayBefore(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectDayIs(string $day, string $field): self;

    /**
     * @param string $day
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectDayIsNot(string $day, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectMonthAfter(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectMonthBefore(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectMonthIs(string $month, string $field): self;

    /**
     * @param string $month
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectMonthIsNot(string $month, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectYearAfter(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectYearBefore(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectYearIs(string $year, string $field): self;

    /**
     * @param string $year
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function objectYearIsNot(string $year, string $field): self;

    /**
     * Define which accounts can be part of the source and destination transactions.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setAccounts(Collection $accounts): self;

    /**
     * Collect transactions after a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setAfter(Carbon $date): self;

    /**
     * Collect transactions before a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setBefore(Carbon $date): self;

    /**
     * Limit the search to a specific bill.
     *
     * @param Bill $bill
     *
     * @return GroupCollectorInterface
     */
    public function setBill(Bill $bill): self;

    /**
     * Limit the search to a specific set of bills.
     *
     * @param Collection $bills
     *
     * @return GroupCollectorInterface
     */
    public function setBills(Collection $bills): self;

    /**
     * Both source AND destination must be in this list of accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setBothAccounts(Collection $accounts): self;

    /**
     * Limit the search to a specific budget.
     *
     * @param Budget $budget
     *
     * @return GroupCollectorInterface
     */
    public function setBudget(Budget $budget): self;

    /**
     * Limit the search to a specific set of budgets.
     *
     * @param Collection $budgets
     *
     * @return GroupCollectorInterface
     */
    public function setBudgets(Collection $budgets): self;

    /**
     * Limit the search to a specific bunch of categories.
     *
     * @param Collection $categories
     *
     * @return GroupCollectorInterface
     */
    public function setCategories(Collection $categories): self;

    /**
     * Limit the search to a specific category.
     *
     * @param Category $category
     *
     * @return GroupCollectorInterface
     */
    public function setCategory(Category $category): self;

    /**
     * Collect transactions created on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setCreatedAt(Carbon $date): self;

    /**
     * Limit results to a specific currency, either foreign or normal one.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function setCurrency(TransactionCurrency $currency): self;

    /**
     * Set destination accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setDestinationAccounts(Collection $accounts): self;

    /**
     * Set the end time of the results to return.
     *
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function setEnd(Carbon $end): self;

    /**
     * @param bool $expandGroupSearch
     */
    public function setExpandGroupSearch(bool $expandGroupSearch): self;

    /**
     * Look for specific external ID's.
     *
     * @param string $externalId
     *
     * @return GroupCollectorInterface
     */
    public function setExternalId(string $externalId): self;

    /**
     * @param string $url
     *
     * @return GroupCollectorInterface
     */
    public function setExternalUrl(string $url): self;

    /**
     * Limit results to a specific foreign currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return GroupCollectorInterface
     */
    public function setForeignCurrency(TransactionCurrency $currency): self;

    /**
     * Limit the result to a set of specific transaction groups.
     *
     * @param array $groupIds
     *
     * @return GroupCollectorInterface
     */
    public function setIds(array $groupIds): self;

    /**
     * Look for specific external ID's.
     *
     * @param string $internalReference
     *
     * @return GroupCollectorInterface
     */
    public function setInternalReference(string $internalReference): self;

    /**
     * Limit the result to a set of specific transaction journals.
     *
     * @param array $journalIds
     *
     * @return GroupCollectorInterface
     */
    public function setJournalIds(array $journalIds): self;

    /**
     * Limit the number of returned entries.
     *
     * @param int $limit
     *
     * @return GroupCollectorInterface
     */
    public function setLimit(int $limit): self;

    /**
     * Collect transactions after a specific date.
     *
     * @param Carbon $date
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setMetaAfter(Carbon $date, string $field): self;

    /**
     * Collect transactions before a specific date.
     *
     * @param Carbon $date
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setMetaBefore(Carbon $date, string $field): self;

    /**
     * Set the start and end time of the results to return, based on meta data.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setMetaDateRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * Define which accounts can NOT be part of the source and destination transactions.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setNotAccounts(Collection $accounts): self;

    /**
     * @param Carbon $date
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setObjectAfter(Carbon $date, string $field): self;

    /**
     * @param Carbon $date
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setObjectBefore(Carbon $date, string $field): self;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function setObjectRange(Carbon $start, Carbon $end, string $field): self;

    /**
     * Set the page to get.
     *
     * @param int $page
     *
     * @return GroupCollectorInterface
     */
    public function setPage(int $page): self;

    /**
     * Set the start and end time of the results to return.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): self;

    /**
     * Look for specific recurring ID's.
     *
     * @param string $recurringId
     *
     * @return GroupCollectorInterface
     */
    public function setRecurrenceId(string $recurringId): self;

    /**
     * Search for words in descriptions.
     *
     * @param array $array
     *
     * @return GroupCollectorInterface
     */
    public function setSearchWords(array $array): self;

    /**
     * @param string $sepaCT
     *
     * @return GroupCollectorInterface
     */
    public function setSepaCT(string $sepaCT): self;

    /**
     * Set source accounts.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setSourceAccounts(Collection $accounts): self;

    /**
     * Set the start time of the results to return.
     *
     * @param Carbon $start
     *
     * @return GroupCollectorInterface
     */
    public function setStart(Carbon $start): self;

    /**
     * Limit results to a specific tag.
     *
     * @param Tag $tag
     *
     * @return GroupCollectorInterface
     */
    public function setTag(Tag $tag): self;

    /**
     * Limit results to a specific set of tags.
     *
     * @param Collection $tags
     *
     * @return GroupCollectorInterface
     */
    public function setTags(Collection $tags): self;

    /**
     * Limit the search to one specific transaction group.
     *
     * @param TransactionGroup $transactionGroup
     *
     * @return GroupCollectorInterface
     */
    public function setTransactionGroup(TransactionGroup $transactionGroup): self;

    /**
     * Limit the included transaction types.
     *
     * @param array $types
     *
     * @return GroupCollectorInterface
     */
    public function setTypes(array $types): self;

    /**
     * Collect transactions updated on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setUpdatedAt(Carbon $date): self;

    /**
     * Set the user object and start the query.
     *
     * @param User $user
     *
     * @return GroupCollectorInterface
     */
    public function setUser(User $user): self;

    /**
     * Set the user group object and start the query.
     *
     * @param UserGroup $userGroup
     *
     * @return GroupCollectorInterface
     */
    public function setUserGroup(UserGroup $userGroup): self;

    /**
     * Only when does not have these tags
     *
     * @param Collection $tags
     *
     * @return GroupCollectorInterface
     */
    public function setWithoutSpecificTags(Collection $tags): self;

    /**
     * Either account can be set, but NOT both. This effectively excludes internal transfers.
     *
     * @param Collection $accounts
     *
     * @return GroupCollectorInterface
     */
    public function setXorAccounts(Collection $accounts): self;

    /**
     * Automatically include all stuff required to make API calls work.
     *
     * @return GroupCollectorInterface
     */
    public function withAPIInformation(): self;

    /**
     * Will include the source and destination account names and types.
     *
     * @return GroupCollectorInterface
     */
    public function withAccountInformation(): self;

    /**
     * Any notes, no matter what.
     *
     * @return GroupCollectorInterface
     */
    public function withAnyNotes(): self;

    /**
     * Add basic info on attachments of transactions.
     *
     * @return GroupCollectorInterface
     */
    public function withAttachmentInformation(): self;

    /**
     * Limit results to transactions without a bill..
     *
     * @return GroupCollectorInterface
     */
    public function withBill(): self;

    /**
     * Include bill name + ID.
     *
     * @return GroupCollectorInterface
     */
    public function withBillInformation(): self;

    /**
     * Limit results to a transactions with a budget.
     *
     * @return GroupCollectorInterface
     */
    public function withBudget(): self;

    /**
     * Will include budget ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withBudgetInformation(): self;

    /**
     * Limit results to a transactions with a category.
     *
     * @return GroupCollectorInterface
     */
    public function withCategory(): self;

    /**
     * Will include category ID + name, if any.
     *
     * @return GroupCollectorInterface
     */
    public function withCategoryInformation(): self;

    /**
     * Transactions with any external ID
     *
     * @return GroupCollectorInterface
     */
    public function withExternalId(): self;

    /**
     * Transactions with any external URL
     *
     * @return GroupCollectorInterface
     */
    public function withExternalUrl(): self;

    /**
     * Transaction must have meta date field X.
     *
     * @param string $field
     *
     * @return GroupCollectorInterface
     */
    public function withMetaDate(string $field): self;

    /**
     * Will include notes.
     *
     * @return GroupCollectorInterface
     */
    public function withNotes(): self;

    /**
     * Add tag info.
     *
     * @return GroupCollectorInterface
     */
    public function withTagInformation(): self;

    /**
     * Limit results to a transactions without a bill.
     *
     * @return GroupCollectorInterface
     */
    public function withoutBill(): self;

    /**
     * Limit results to a transactions without a budget.
     *
     * @return GroupCollectorInterface
     */
    public function withoutBudget(): self;

    /**
     * Limit results to a transactions without a category.
     *
     * @return GroupCollectorInterface
     */
    public function withoutCategory(): self;

    /**
     * Transactions without an external ID
     *
     * @return GroupCollectorInterface
     */
    public function withoutExternalId(): self;

    /**
     * Transactions without an external URL
     *
     * @return GroupCollectorInterface
     */
    public function withoutExternalUrl(): self;

    /**
     * @return GroupCollectorInterface
     */
    public function withoutNotes(): self;

    /**
     * @return GroupCollectorInterface
     */
    public function withoutTags(): self;

    /**
     * @param string $year
     *
     * @return GroupCollectorInterface
     */
    public function yearAfter(string $year): self;

    /**
     * @param string $year
     *
     * @return GroupCollectorInterface
     */
    public function yearBefore(string $year): self;

    /**
     * @param string $year
     *
     * @return GroupCollectorInterface
     */
    public function yearIs(string $year): self;

    /**
     * @param string $year
     *
     * @return GroupCollectorInterface
     */
    public function yearIsNot(string $year): self;


}
