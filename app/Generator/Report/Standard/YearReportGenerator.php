<?php
/**
 * YearReportGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Report\Standard;


use Carbon\Carbon;
use FireflyIII\Generator\Report\ReportGeneratorInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use Illuminate\Support\Collection;

/**
 * Class MonthReportGenerator
 *
 * @package FireflyIII\Generator\Report\Standard
 */
class YearReportGenerator implements ReportGeneratorInterface
{
    /** @var  Collection */
    private $accounts;
    /** @var  Carbon */
    private $end;
    /** @var  Carbon */
    private $start;

    /**
     * @return string
     */
    public function generate(): string
    {
        // and some id's, joined:
        $accountIds = join(',', $this->accounts->pluck('id')->toArray());
        $reportType = 'default';

        // continue!
        return view(
            'reports.default.year',
            compact('accountIds', 'reportType')
        )->with('start', $this->start)->with('end', $this->end)->render();
    }

    /**
     * @param Collection $accounts
     *
     * @return ReportGeneratorInterface
     */
    public function setAccounts(Collection $accounts): ReportGeneratorInterface
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setEndDate(Carbon $date): ReportGeneratorInterface
    {
        $this->end = $date;

        return $this;
    }

    /**
     * @param Carbon $date
     *
     * @return ReportGeneratorInterface
     */
    public function setStartDate(Carbon $date): ReportGeneratorInterface
    {
        $this->start = $date;

        return $this;
    }
}