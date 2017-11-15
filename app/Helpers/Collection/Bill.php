<?php
/**
 * Bill.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collection;

use Carbon\Carbon;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Bill
 *
 * @package FireflyIII\Helpers\Collection
 */
class Bill
{

    /**
     * @var Collection
     */
    private $bills;
    /** @var  Carbon */
    private $endDate;
    /** @var  Carbon */
    private $startDate;

    /**
     *
     */
    public function __construct()
    {
        $this->bills = new Collection;
    }

    /**
     * @param BillLine $bill
     */
    public function addBill(BillLine $bill)
    {
        $this->bills->push($bill);
    }

    /**
     *
     */
    public function filterBills()
    {
        Log::debug('Now in filterBills()');
        /** @var BillRepositoryInterface $repository */
        $repository  = app(BillRepositoryInterface::class);
        $start       = $this->startDate;
        $end         = $this->endDate;
        $lines       = $this->bills->filter(
            function (BillLine $line) use ($repository, $start, $end) {
                // next expected match?
                $date = $start;
                Log::debug(sprintf('Now at bill line for bill "%s"', $line->getBill()->name));
                Log::debug(sprintf('Default date to use is start date: %s', $date->format('Y-m-d')));
                if ($line->isHit()) {
                    $date = $line->getLastHitDate();
                    Log::debug(sprintf('Line was hit, see date: %s. Always include it.', $date->format('Y-m-d')));

                    return $line;
                }
                $expected = $repository->nextExpectedMatch($line->getBill(), $date);
                Log::debug(sprintf('Next expected match is %s', $expected->format('Y-m-d')));
                if ($expected <= $end && $expected >= $start) {
                    Log::debug('This date is inside report limits');

                    return $line;
                }
                Log::debug('This date is OUTSIDE report limits');

                return false;
            }
        );
        $this->bills = $lines;
    }

    /**
     * @return Collection
     */
    public function getBills(): Collection
    {
        $set = $this->bills->sortBy(
            function (BillLine $bill) {
                $active = intval($bill->getBill()->active) === 0 ? 1 : 0;
                $name   = $bill->getBill()->name;

                return $active . $name;
            }
        );


        return $set;
    }

    /**
     * @param Carbon $endDate
     */
    public function setEndDate(Carbon $endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @param Carbon $startDate
     */
    public function setStartDate(Carbon $startDate)
    {
        $this->startDate = $startDate;
    }
}
