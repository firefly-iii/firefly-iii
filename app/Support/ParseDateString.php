<?php

/**
 * ParseDateString.php
 * Copyright (c) 2020 james@firefly-iii.org
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
namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use Log;

/**
 * Class ParseDateString
 */
class ParseDateString
{
    private $keywords
        = [
            'today',
            'yesterday',
            'tomorrow',
            'start of this week',
            'end of this week',
            'start of this month',
            'end of this month',
            'start of this quarter',
            'end of this quarter',
            'start of this year',
            'end of this year',
        ];

    /**
     * @param string $date
     *
     * @return Carbon
     */
    public function parseDate(string $date): Carbon
    {
        $date = strtolower($date);
        // parse keywords:
        if (in_array($date, $this->keywords, true)) {
            return $this->parseKeyword($date);
        }

        // if regex for YYYY-MM-DD:
        $pattern = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][\d]|3[01])$/';
        if (preg_match($pattern, $date)) {
            return $this->parseDefaultDate($date);
        }

        // if + or -:
        if (0 === strpos($date, '+') || 0 === strpos($date, '-')) {

            return $this->parseRelativeDate($date);
        }
        if ('xxxx-xx-xx' === strtolower($date)) {
            throw new FireflyException(sprintf('[a]Not a recognised date format: "%s"', $date));
        }
        // can't do a partial year:
        $substrCount = substr_count(substr($date, 0, 4), 'x', 0);
        if (10 === strlen($date) && $substrCount > 0 && $substrCount < 4) {
            throw new FireflyException(sprintf('[b]Not a recognised date format: "%s"', $date));
        }

        // maybe a date range
        if (10 === strlen($date) && (false !== strpos($date, 'xx') || false !== strpos($date, 'xxxx'))) {
            Log::debug(sprintf('[c]Detected a date range ("%s"), return a fake date.', $date));
            // very lazy way to parse the date without parsing it, because this specific function
            // cant handle date ranges.
            return new Carbon('1984-09-17');
        }
        // maybe a year, nothing else?
        if (4 === strlen($date) && is_numeric($date) && (int) $date > 1000 && (int) $date <= 3000) {
            return new Carbon(sprintf('%d-01-01', $date));
        }

        throw new FireflyException(sprintf('[d]Not a recognised date format: "%s"', $date));
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    public function isDateRange(string $date): bool
    {
        $date = strtolower($date);
        // not 10 chars:
        if (10 !== strlen($date)) {
            return false;
        }
        // all x'es
        if ('xxxx-xx-xx' === strtolower($date)) {
            return false;
        }
        // no x'es
        if (false === strpos($date, 'xx') && false === strpos($date, 'xxxx')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    public function parseRange(string $date, Carbon $journalDate): array
    {
        // several types of range can be submitted
        switch (true) {
            default:
                break;
            case $this->isDayRange($date):
                return $this->parseDayRange($date, $journalDate);
            case $this->isMonthRange($date):
                return $this->parseMonthRange($date, $journalDate);
            case $this->isYearRange($date):
                return $this->parseYearRange($date, $journalDate);
            case $this->isMonthDayRange($date):
                return $this->parseMonthDayRange($date, $journalDate);
            case $this->isDayYearRange($date):
                return $this->parseDayYearRange($date, $journalDate);
            case $this->isMonthYearRange($date):
                return $this->parseMonthYearRange($date, $journalDate);
        }

        return [
            'start' => new Carbon('1984-09-17'),
            'end'   => new Carbon('1984-09-17'),
        ];
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isDayRange(string $date): bool
    {
        // if regex for xxxx-xx-DD:
        $pattern = '/^xxxx-xx-(0[1-9]|[12][\d]|3[01])$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a day range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a day range.', $date));

        return false;
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    protected function parseDayRange(string $date, Carbon $journalDate): array
    {
        // format of string is xxxx-xx-DD
        $validDate = str_replace(['xxxx'], [$journalDate->year], $date);
        $validDate = str_replace(['xx'], [$journalDate->format('m')], $validDate);
        Log::debug(sprintf('parseDayRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfDay();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isMonthRange(string $date): bool
    {
        // if regex for xxxx-MM-xx:
        $pattern = '/^xxxx-(0[1-9]|1[012])-xx$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a month range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a month range.', $date));

        return false;
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isMonthYearRange(string $date): bool
    {
        // if regex for YYYY-MM-xx:
        $pattern = '/^(19|20)\d\d-(0[1-9]|1[012])-xx$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a month/year range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a month/year range.', $date));

        return false;
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isYearRange(string $date): bool
    {
        // if regex for YYYY-xx-xx:
        $pattern = '/^(19|20)\d\d-xx-xx$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a year range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a year range.', $date));

        return false;
    }
    /**
     * @param string $date
     *
     * @return Carbon
     */
    protected function parseDefaultDate(string $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $date);
    }

    /**
     * @param string $keyword
     *
     * @return Carbon
     */
    protected function parseKeyword(string $keyword): Carbon
    {
        $today = Carbon::today()->startOfDay();
        switch ($keyword) {
            default:
            case 'today':
                return $today;
            case 'yesterday':
                return $today->subDay();
            case 'tomorrow':
                return $today->addDay();
            case 'start of this week':
                return $today->startOfWeek();
            case 'end of this week':
                return $today->endOfWeek();
            case 'start of this month':
                return $today->startOfMonth();
            case 'end of this month':
                return $today->endOfMonth();
            case 'start of this quarter':
                return $today->startOfQuarter();
            case 'end of this quarter':
                return $today->endOfQuarter();
            case 'start of this year':
                return $today->startOfYear();
            case 'end of this year':
                return $today->endOfYear();
        }
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    protected function parseMonthRange(string $date, Carbon $journalDate): array
    {
        // because 31 would turn February into March unexpectedly and the exact day is irrelevant here.
        $day = $journalDate->format('d');
        if ((int) $day > 28) {
            $day = '28';
        }

        // format of string is xxxx-MM-xx
        $validDate = str_replace(['xxxx'], [$journalDate->year], $date);
        $validDate = str_replace(['xx'], [$day], $validDate);
        Log::debug(sprintf('parseMonthRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfMonth();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    protected function parseMonthYearRange(string $date, Carbon $journalDate): array
    {
        // because 31 would turn February into March unexpectedly and the exact day is irrelevant here.
        $day = $journalDate->format('d');
        if ((int) $day > 28) {
            $day = '28';
        }

        // format of string is YYYY-MM-xx
        $validDate = str_replace(['xx'], [$day], $date);
        Log::debug(sprintf('parseMonthYearRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfMonth();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /**
     * @param string $date
     *
     * @return Carbon
     */
    protected function parseRelativeDate(string $date): Carbon
    {
        Log::debug(sprintf('Now in parseRelativeDate("%s")', $date));
        $parts     = explode(' ', $date);
        $today     = Carbon::today()->startOfDay();
        $functions = [
            [
                'd' => 'subDays',
                'w' => 'subWeeks',
                'm' => 'subMonths',
                'q' => 'subQuarters',
                'y' => 'subYears',
            ], [
                'd' => 'addDays',
                'w' => 'addWeeks',
                'm' => 'addMonths',
                'q' => 'addQuarters',
                'y' => 'addYears',
            ],
        ];

        /** @var string $part */
        foreach ($parts as $part) {
            Log::debug(sprintf('Now parsing part "%s"', $part));
            $part = trim($part);

            // verify if correct
            $pattern = '/[+-]\d+[wqmdy]/';
            $res     = preg_match($pattern, $part);
            if (0 === $res || false === $res) {
                Log::error(sprintf('Part "%s" does not match regular expression. Will be skipped.', $part));
                continue;
            }
            $direction = 0 === strpos($part, '+') ? 1 : 0;
            $period    = $part[strlen($part) - 1];
            $number    = (int) substr($part, 1, -1);
            if (!isset($functions[$direction][$period])) {
                Log::error(sprintf('No method for direction %d and period "%s".', $direction, $period));
                continue;
            }
            $func = $functions[$direction][$period];
            Log::debug(sprintf('Will now do %s(%d) on %s', $func, $number, $today->format('Y-m-d')));
            $today->$func($number);
            Log::debug(sprintf('Resulting date is %s', $today->format('Y-m-d')));

        }

        return $today;
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    protected function parseYearRange(string $date, Carbon $journalDate): array
    {
        // format of string is YYYY-xx-xx
        // kind of a convulted way of replacing variables but I'm lazy.
        $validDate = str_replace(['xx-xx'], [sprintf('%s-xx', $journalDate->format('m'))], $date);
        $validDate = str_replace(['xx'], [$journalDate->format('d')], $validDate);
        Log::debug(sprintf('parseYearRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfYear();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfYear();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isMonthDayRange(string $date): bool
    {
        // if regex for xxxx-MM-DD:
        $pattern = '/^xxxx-(0[1-9]|1[012])-(0[1-9]|[12][\d]|3[01])$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a month/day range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a month/day range.', $date));

        return false;
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    protected function isDayYearRange(string $date): bool
    {
        // if regex for YYYY-xx-DD:
        $pattern = '/^(19|20)\d\d-xx-(0[1-9]|[12][\d]|3[01])$/';
        if (preg_match($pattern, $date)) {
            Log::debug(sprintf('"%s" is a day/year range.', $date));

            return true;
        }
        Log::debug(sprintf('"%s" is not a day/year range.', $date));

        return false;
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    private function parseMonthDayRange(string $date, Carbon $journalDate): array
    {
        // Any year.
        // format of string is xxxx-MM-DD
        $validDate = str_replace(['xxxx'], [$journalDate->year], $date);
        Log::debug(sprintf('parseMonthDayRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfDay();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    /**
     * @param string $date
     * @param Carbon $journalDate
     *
     * @return array
     */
    private function parseDayYearRange(string $date, Carbon $journalDate): array
    {
        // Any year.
        // format of string is YYYY-xx-DD
        $validDate = str_replace(['xx'], [$journalDate->format('m')], $date);
        Log::debug(sprintf('parseDayYearRange: Parsed "%s" into "%s"', $date, $validDate));
        $start = Carbon::createFromFormat('Y-m-d', $validDate)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $validDate)->endOfDay();

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

}
