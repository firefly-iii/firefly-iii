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
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface.
 */
interface BillRepositoryInterface
{

    /**
     * @param Bill $bill
     */
    public function unlinkAll(Bill $bill): void;

    /**
     * @param Bill $bill
     *
     * @return bool
     */
    public function destroy(Bill $bill): bool;

    /**
     * Find a bill by ID.
     *
     * @param int $billId
     *
     * @return Bill|null
     */
    public function find(int $billId): ?Bill;

    /**
     * Find bill by parameters.
     *
     * @param int|null    $billId
     * @param string|null $billName
     *
     * @return Bill|null
     */
    public function findBill(?int $billId, ?string $billName): ?Bill;

    /**
     * Find a bill by name.
     *
     * @param string $name
     *
     * @return Bill|null
     */
    public function findByName(string $name): ?Bill;

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection;

    /**
     * Get all attachments.
     *
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getAttachments(Bill $bill): Collection;

    /**
     * @return Collection
     */
    public function getBills(): Collection;

    /**
     * Gets the bills which have some kind of relevance to the accounts mentioned.
     *
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection;

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string;

    /**
     * Get the total amount of money paid for the users active bills in the date range given,
     * grouped per currency.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getBillsPaidInRangePerCurrency(Carbon $start, Carbon $end): array;

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string;

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getBillsUnpaidInRangePerCurrency(Carbon $start, Carbon $end): array;

    /**
     * Get all bills with these ID's.
     *
     * @param array $billIds
     *
     * @return Collection
     */
    public function getByIds(array $billIds): Collection;

    /**
     * Get text or return empty string.
     *
     * @param Bill $bill
     *
     * @return string
     */
    public function getNoteText(Bill $bill): string;

    /**
     * @param Bill $bill
     *
     * @return string
     */
    public function getOverallAverage(Bill $bill): string;

    /**
     * @param int $size
     *
     * @return LengthAwarePaginator
     */
    public function getPaginator(int $size): LengthAwarePaginator;

    /**
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Return all rules for one bill
     *
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getRulesForBill(Bill $bill): Collection;

    /**
     * Return all rules related to the bills in the collection, in an associative array:
     * 5= billid
     *
     * 5 => [['id' => 1, 'title' => 'Some rule'],['id' => 2, 'title' => 'Some other rule']]
     *
     * @param Collection $collection
     *
     * @return array
     */
    public function getRulesForBills(Collection $collection): array;

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return string
     */
    public function getYearAverage(Bill $bill, Carbon $date): string;

    /**
     * Link a set of journals to a bill.
     *
     * @param Bill       $bill
     * @param array $transactions
     */
    public function linkCollectionToBill(Bill $bill, array $transactions): void;

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBill(string $query): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return Bill
     * @throws FireflyException
     */
    public function store(array $data): Bill;

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill;
}
