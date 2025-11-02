<?php

/**
 * FiscalHelper.php
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

namespace FireflyIII\Helpers\Fiscal;

use Carbon\Carbon;
use FireflyIII\Support\Facades\Preferences;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class FiscalHelper.
 */
class FiscalHelper implements FiscalHelperInterface
{
    /** @var bool */
    protected $useCustomFiscalYear;

    /**
     * FiscalHelper constructor.
     */
    public function __construct()
    {
        $this->useCustomFiscalYear = (bool) Preferences::get('customFiscalYear', false)->data;
    }

    /**
     * @return Carbon date object
     */
    public function endOfFiscalYear(Carbon $date): Carbon
    {
        // Log::debug(sprintf('Now in endOfFiscalYear(%s).', $date->format('Y-m-d')));
        $endDate = $this->startOfFiscalYear($date);
        if (true === $this->useCustomFiscalYear) {
            // add 1 year and sub 1 day
            $endDate->addYear();
            $endDate->subDay();
        }
        if (false === $this->useCustomFiscalYear) {
            $endDate->endOfYear();
        }
        // Log::debug(sprintf('Result of endOfFiscalYear(%s) = %s', $date->format('Y-m-d'), $endDate->format('Y-m-d')));

        return $endDate;
    }

    /**
     * @return Carbon date object
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function startOfFiscalYear(Carbon $date): Carbon
    {
        // get start mm-dd. Then create a start date in the year passed.
        $startDate = clone $date;
        if (true === $this->useCustomFiscalYear) {
            $prefStartStr = Preferences::get('fiscalYearStart', '01-01')->data;
            if (is_array($prefStartStr)) {
                $prefStartStr = '01-01';
            }
            $prefStartStr = (string) $prefStartStr;
            [$mth, $day]  = explode('-', $prefStartStr);
            $startDate->day((int) $day)->month((int) $mth);

            // if start date is after passed date, sub 1 year.
            if ($startDate > $date) {
                $startDate->subYear();
            }
        }
        if (false === $this->useCustomFiscalYear) {
            $startDate->startOfYear();
        }

        // Log::debug(sprintf('Result of startOfFiscalYear(%s) = %s', $date->format('Y-m-d'), $startDate->format('Y-m-d')));

        return $startDate;
    }
}
