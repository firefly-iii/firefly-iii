<?php

declare(strict_types=1);
/*
 * BillRepositoryInterface.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Administration\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface
 */
interface BillRepositoryInterface
{
    /**
     * TODO duplicate of other repos
     * Add correct order to bills.
     */
    public function correctOrder(): void;

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection;

    /**
     * @return Collection
     */
    public function getBills(): Collection;

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * TODO duplicate of method in other billrepositoryinterface
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether it is there already, is not relevant.
     *
     * TODO duplicate of method in other bill repos
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return Carbon
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * Collect multi-currency of sum of bills already paid.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function sumPaidInRange(Carbon $start, Carbon $end): array;

    /**
     * Collect multi-currency of sum of bills yet to pay.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function sumUnpaidInRange(Carbon $start, Carbon $end): array;
}
