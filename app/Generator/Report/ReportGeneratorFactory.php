<?php
/**
 * ReportGeneratorFactory.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
        // more than one year date difference means multi year report.
        if ($start->diffInMonths($end) > 12) {
            $period = 'MultiYear';
        }
        // more than two months date difference means year report.
        if ($start->diffInMonths($end) > 1) {
            $period = 'Year';
        }

        $class = sprintf('FireflyIII\Generator\Report\%s\%sReportGenerator', $type, $period);
        if (class_exists($class)) {
            /** @var ReportGeneratorInterface $obj */
            $obj = new $class;
            $obj->setStartDate($start);
            $obj->setEndDate($end);

            return $obj;
        }
        throw new FireflyException(sprintf('Class "%s" does not exist.', $class));
    }
}