<?php

/**
 * BillRepositoryInterface.php
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

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface.
 */
interface BillRepositoryInterface
{
    public function billEndsWith(string $query, int $limit): Collection;

    public function billStartsWith(string $query, int $limit): Collection;
    public function setUserGroup(UserGroup $userGroup): void;

    /**
     * Add correct order to bills.
     */
    public function correctOrder(): void;

    public function destroy(Bill $bill): bool;

    public function destroyAll(): void;

    /**
     * Find a bill by ID.
     */
    public function find(int $billId): ?Bill;

    /**
     * Find bill by parameters.
     */
    public function findBill(?int $billId, ?string $billName): ?Bill;

    /**
     * Find a bill by name.
     */
    public function findByName(string $name): ?Bill;

    public function getActiveBills(): Collection;

    /**
     * Get all attachments.
     */
    public function getAttachments(Bill $bill): Collection;

    public function getBills(): Collection;

    /**
     * Gets the bills which have some kind of relevance to the accounts mentioned.
     */
    public function getBillsForAccounts(Collection $accounts): Collection;

    /**
     * Get all bills with these ID's.
     */
    public function getByIds(array $billIds): Collection;

    /**
     * Get text or return empty string.
     */
    public function getNoteText(Bill $bill): string;

    public function getOverallAverage(Bill $bill): array;

    public function getPaginator(int $size): LengthAwarePaginator;

    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Return all rules for one bill
     */
    public function getRulesForBill(Bill $bill): Collection;

    /**
     * Return all rules related to the bills in the collection, in an associative array:
     * 5= billid
     *
     * 5 => [['id' => 1, 'title' => 'Some rule'],['id' => 2, 'title' => 'Some other rule']]
     */
    public function getRulesForBills(Collection $collection): array;

    public function getYearAverage(Bill $bill, Carbon $date): array;

    /**
     * Link a set of journals to a bill.
     */
    public function linkCollectionToBill(Bill $bill, array $transactions): void;

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon;

    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon;

    public function removeObjectGroup(Bill $bill): Bill;

    public function searchBill(string $query, int $limit): Collection;

    public function setObjectGroup(Bill $bill, string $objectGroupTitle): Bill;

    /**
     * Set specific piggy bank to specific order.
     */
    public function setOrder(Bill $bill, int $order): void;

    public function setUser(null|Authenticatable|User $user): void;

    /**
     * @throws FireflyException
     */
    public function store(array $data): Bill;

    /**
     * Collect multi-currency of sum of bills already paid.
     */
    public function sumPaidInRange(Carbon $start, Carbon $end): array;

    /**
     * Collect multi-currency of sum of bills yet to pay.
     */
    public function sumUnpaidInRange(Carbon $start, Carbon $end): array;

    public function unlinkAll(Bill $bill): void;

    public function update(Bill $bill, array $data): Bill;
}
