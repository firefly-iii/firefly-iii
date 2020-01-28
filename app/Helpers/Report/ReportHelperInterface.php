<?php
/**
 * ReportHelperInterface.php
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

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Bill as BillCollection;
use Illuminate\Support\Collection;

/**
 * Interface ReportHelperInterface.
 */
interface ReportHelperInterface
{
    /**
     * This method generates a full report for the given period on all
     * the users bills and their payments.
     *
     * Excludes bills which have not had a payment on the mentioned accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getBillReport(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * Generate a list of months.
     *
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date): array;
}
