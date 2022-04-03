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
        if (!str_contains($date, 'xx') && !str_contains($date, 'xxxx')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $date
     *
     * @return Carbon
     * @throws FireflyException
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
        if (str_starts_with($date, '+') || str_starts_with($date, '-')) {

            return $this->parseRelativeDate($date);
        }
        if ('xxxx-xx-xx' === strtolower($date)) {
            throw new FireflyException(sprintf('[a] Not a recognised date format: "%s"', $date));
        }
        // can't do a partial year:
        $substrCount = substr_count(substr($date, 0, 4), 'x', 0);
        if (10 === strlen($date) && $substrCount > 0 && $substrCount < 4) {
            throw new FireflyException(sprintf('[b] Not a recognised date format: "%s"', $date));
        }

        // maybe a date range
        if (10 === strlen($date) && (str_contains($date, 'xx') || str_contains($date, 'xxxx'))) {
            Log::debug(sprintf('[c] Detected a date range ("%s"), return a fake date.', $date));
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
     * @param string $keyword
     *
     * @return Carbon
     */
    protected function parseKeyword(string $keyword): Carbon
    {
        $today = Carbon::today()->startOfDay();

        return match ($keyword) {
            default => $today,
            'yesterday' => $today->subDay(),
            'tomorrow' => $today->addDay(),
            'start of this week' => $today->startOfWeek(),
            'end of this week' => $today->endOfWeek(),
            'start of this month' => $today->startOfMonth(),
            'end of this month' => $today->endOfMonth(),
            'start of this quarter' => $today->startOfQuarter(),
            'end of this quarter' => $today->endOfQuarter(),
            'start of this year' => $today->startOfYear(),
            'end of this year' => $today->endOfYear(),
        };
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
            $direction = str_starts_with($part, '+') ? 1 : 0;
            $period    = $part[strlen($part) - 1];
            $number    = (int) substr($part, 1, -1);
            if (!array_key_exists($period, $functions[$direction])) {
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
     *
     * @return array
     */
    public function parseRange(string $date): array
    {
        // several types of range can be submitted
        $result = [
            'exact' => new Carbon('1984-09-17'),
        ];
        switch (true) {
            default:
                break;
            case $this->isDayRange($date):
                $result = $this->parseDayRange($date);
                break;
            case $this->isMonthRange($date):
                $result = $this->parseMonthRange($date);
                break;
            case $this->isYearRange($date):
                $result = $this->parseYearRange($date);
                break;
            case $this->isMonthDayRange($date):
                $result = $this->parseMonthDayRange($date);
                break;
            case $this->isDayYearRange($date):
                $result = $this->parseDayYearRange($date);
                break;
            case $this->isMonthYearRange($date):
                $result = $this->parseMonthYearRange($date);
                break;
        }

        return $result;
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
     * format of string is xxxx-xx-DD
     *
     * @param string $date
     *
     * @return array
     */
    protected function parseDayRange(string $date): array
    {
        $parts = explode('-', $date);

        return [
            'day' => $parts[2],
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
     * format of string is xxxx-MM-xx
     *
     * @param string $date
     *
     * @return array
     */
    protected function parseMonthRange(string $date): array
    {
        Log::debug(sprintf('parseMonthRange: Parsed "%s".', $date));
        $parts = explode('-', $date);

        return [
            'month' => $parts[1],
        ];
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
     * format of string is YYYY-xx-xx
     *
     * @param string $date
     *
     * @return array
     */
    protected function parseYearRange(string $date): array
    {
        Log::debug(sprintf('parseYearRange: Parsed "%s"', $date));
        $parts = explode('-', $date);

        return [
            'year' => $parts[0],
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
     * format of string is xxxx-MM-DD
     *
     * @param string $date
     *
     * @return array
     */
    private function parseMonthDayRange(string $date): array
    {
        Log::debug(sprintf('parseMonthDayRange: Parsed "%s".', $date));
        $parts = explode('-', $date);

        return [
            'month' => $parts[1],
            'day'   => $parts[2],
        ];
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
     * format of string is YYYY-xx-DD
     *
     * @param string $date
     *
     * @return array
     */
    private function parseDayYearRange(string $date): array
    {
        Log::debug(sprintf('parseDayYearRange: Parsed "%s".', $date));
        $parts = explode('-', $date);

        return [
            'year' => $parts[0],
            'day'  => $parts[2],
        ];
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
     * format of string is YYYY-MM-xx
     *
     * @param string $date
     *
     * @return array
     */
    protected function parseMonthYearRange(string $date): array
    {
        Log::debug(sprintf('parseMonthYearRange: Parsed "%s".', $date));
        $parts = explode('-', $date);

        return [
            'year'  => $parts[0],
            'month' => $parts[1],
        ];
    }

}
