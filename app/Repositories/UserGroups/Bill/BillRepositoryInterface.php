<?php

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

declare(strict_types=1);

namespace FireflyIII\Repositories\UserGroups\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface
 *
 * @deprecated
 */
interface BillRepositoryInterface
{
    /**
     * TODO duplicate of other repos
     * Add correct order to bills.
     */
    public function correctOrder(): void;

    public function getActiveBills(): Collection;

    public function getBills(): Collection;

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * TODO duplicate of method in other billrepositoryinterface
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether it is there already, is not relevant.
     *
     * TODO duplicate of method in other bill repos
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * Collect multi-currency of sum of bills already paid.
     */
    public function sumPaidInRange(Carbon $start, Carbon $end): array;

    /**
     * Collect multi-currency of sum of bills yet to pay.
     */
    public function sumUnpaidInRange(Carbon $start, Carbon $end): array;
}
