<?php
/**
 * Bill.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
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
                $active = intval($bill->getBill()->active) == 0 ? 1 : 0;
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
