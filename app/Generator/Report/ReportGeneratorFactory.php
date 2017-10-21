<?php
/**
 * ReportGeneratorFactory.php
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

namespace FireflyIII\Generator\Report;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;

/**
 * Class ReportGeneratorFactory
 *
 * @package FireflyIII\Generator\Report
 */
class ReportGeneratorFactory
{

    /**
     * @param string $type
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return ReportGeneratorInterface
     * @throws FireflyException
     */
    public static function reportGenerator(string $type, Carbon $start, Carbon $end): ReportGeneratorInterface
    {
        $period = 'Month';
        // more than two months date difference means year report.
        if ($start->diffInMonths($end) > 1) {
            $period = 'Year';
        }

        // more than one year date difference means multi year report.
        if ($start->diffInMonths($end) > 12) {
            $period = 'MultiYear';
        }


        $class = sprintf('FireflyIII\Generator\Report\%s\%sReportGenerator', $type, $period);
        if (class_exists($class)) {
            /** @var ReportGeneratorInterface $obj */
            $obj = app($class);
            $obj->setStartDate($start);
            $obj->setEndDate($end);

            return $obj;
        }
        throw new FireflyException(sprintf('Cannot generate report. There is no "%s"-report for period "%s".', $type, $period));
    }
}
