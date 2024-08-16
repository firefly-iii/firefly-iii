<?php

/**
 * Navigation.php
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

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\IntervalException;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Support\Calendar\Calculator;
use FireflyIII\Support\Calendar\Periodicity;
use Illuminate\Support\Facades\Log;

/**
 * Class Navigation.
 */
class Navigation
{
    private Calculator $calculator;

    public function __construct(?Calculator $calculator = null)
    {
        $this->calculator = $calculator instanceof Calculator ? $calculator : new Calculator();
    }

    public function addPeriod(Carbon $theDate, string $repeatFreq, int $skip = 0): Carbon
    {
        $date        = clone $theDate;
        $functionMap = [
            '1D'        => Periodicity::Daily,
            'daily'     => Periodicity::Daily,
            '1W'        => Periodicity::Weekly,
            'weekly'    => Periodicity::Weekly,
            'week'      => Periodicity::Weekly,
            '1M'        => Periodicity::Monthly,
            'month'     => Periodicity::Monthly,
            'monthly'   => Periodicity::Monthly,
            '3M'        => Periodicity::Quarterly,
            'quarter'   => Periodicity::Quarterly,
            'quarterly' => Periodicity::Quarterly,
            '6M'        => Periodicity::HalfYearly,
            'half-year' => Periodicity::HalfYearly,
            'year'      => Periodicity::Yearly,
            'yearly'    => Periodicity::Yearly,
            '1Y'        => Periodicity::Yearly,
            'custom'    => Periodicity::Monthly, // custom? just add one month.
            // last X periods? Jump the relevant month / quarter / year
            'last7'     => Periodicity::Weekly,
            'last30'    => Periodicity::Monthly,
            'last90'    => Periodicity::Quarterly,
            'last365'   => Periodicity::Yearly,
            'MTD'       => Periodicity::Monthly,
            'QTD'       => Periodicity::Quarterly,
            'YTD'       => Periodicity::Yearly,
        ];

        if (!array_key_exists($repeatFreq, $functionMap)) {
            Log::error(sprintf(
                           'The periodicity %s is unknown. Choose one of available periodicity: %s',
                           $repeatFreq,
                           implode(', ', array_keys($functionMap))
                       ));

            return $theDate;
        }

        return $this->nextDateByInterval($date, $functionMap[$repeatFreq], $skip);
    }

    public function nextDateByInterval(Carbon $epoch, Periodicity $periodicity, int $skipInterval = 0): Carbon
    {
        try {
            return $this->calculator->nextDateByInterval($epoch, $periodicity, $skipInterval);
        } catch (IntervalException $exception) {
            Log::warning($exception->getMessage(), ['exception' => $exception]);
        } catch (\Throwable $exception) { // @phpstan-ignore-line
            Log::error($exception->getMessage(), ['exception' => $exception]);
        }

        Log::debug(
            'Any error occurred to calculate the next date.',
            ['date' => $epoch, 'periodicity' => $periodicity->name, 'skipInterval' => $skipInterval]
        );

        return $epoch;
    }

    public function blockPeriods(Carbon $start, Carbon $end, string $range): array
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $periods = [];
        // first, 13 periods of [range]
        $loopCount = 0;
        $loopDate  = clone $end;
        $workStart = clone $loopDate;
        $workEnd   = clone $loopDate;
        while ($loopCount < 13) {
            // make range:
            $workStart = $this->startOfPeriod($workStart, $range);
            $workEnd   = $this->endOfPeriod($workStart, $range);

            // make sure we don't go overboard
            if ($workEnd->gt($start)) {
                $periods[] = [
                    'start'  => clone $workStart,
                    'end'    => clone $workEnd,
                    'period' => $range,
                ];
            }
            // skip to the next period:
            $workStart->subDay()->startOfDay();
            ++$loopCount;
        }
        // if $workEnd is still before $start, continue on a yearly basis:
        $loopCount = 0;
        if ($workEnd->gt($start)) {
            while ($workEnd->gt($start) && $loopCount < 20) {
                // make range:
                $workStart = app('navigation')->startOfPeriod($workStart, '1Y');
                $workEnd   = app('navigation')->endOfPeriod($workStart, '1Y');

                // make sure we don't go overboard
                if ($workEnd->gt($start)) {
                    $periods[] = [
                        'start'  => clone $workStart,
                        'end'    => clone $workEnd,
                        'period' => '1Y',
                    ];
                }
                // skip to the next period:
                $workStart->subDay()->startOfDay();
                ++$loopCount;
            }
        }

        return $periods;
    }

    public function startOfPeriod(Carbon $theDate, string $repeatFreq): Carbon
    {
        $date = clone $theDate;
        Log::debug(sprintf('Now in startOfPeriod("%s", "%s")', $date->toIso8601String(), $repeatFreq));
        $functionMap = [
            '1D'        => 'startOfDay',
            'daily'     => 'startOfDay',
            '1W'        => 'startOfWeek',
            'week'      => 'startOfWeek',
            'weekly'    => 'startOfWeek',
            'month'     => 'startOfMonth',
            '1M'        => 'startOfMonth',
            'monthly'   => 'startOfMonth',
            '3M'        => 'firstOfQuarter',
            'quarter'   => 'firstOfQuarter',
            'quarterly' => 'firstOfQuarter',
            'year'      => 'startOfYear',
            'yearly'    => 'startOfYear',
            '1Y'        => 'startOfYear',
            'MTD'       => 'startOfMonth',
        ];

        $parameterMap = [
            'startOfWeek' => [Carbon::MONDAY],
        ];

        if (array_key_exists($repeatFreq, $functionMap)) {
            $function = $functionMap[$repeatFreq];
            Log::debug(sprintf('Function is ->%s()', $function));
            if (array_key_exists($function, $parameterMap)) {
                Log::debug(sprintf('Parameter map, function becomes ->%s(%s)', $function, implode(', ', $parameterMap[$function])));
                $date->{$function}($parameterMap[$function][0]); // @phpstan-ignore-line
                Log::debug(sprintf('Result is "%s"', $date->toIso8601String()));

                return $date;
            }

            $date->{$function}(); // @phpstan-ignore-line
            Log::debug(sprintf('Result is "%s"', $date->toIso8601String()));

            return $date;
        }
        if ('half-year' === $repeatFreq || '6M' === $repeatFreq) {
            $skipTo = $date->month > 7 ? 6 : 0;
            $date->startOfYear()->addMonths($skipTo);
            Log::debug(sprintf('Custom call for "%s": addMonths(%d)', $repeatFreq, $skipTo));
            Log::debug(sprintf('Result is "%s"', $date->toIso8601String()));

            return $date;
        }

        $result = match ($repeatFreq) {
            'last7'   => $date->subDays(7)->startOfDay(),
            'last30'  => $date->subDays(30)->startOfDay(),
            'last90'  => $date->subDays(90)->startOfDay(),
            'last365' => $date->subDays(365)->startOfDay(),
            'MTD'     => $date->startOfMonth()->startOfDay(),
            'QTD'     => $date->firstOfQuarter()->startOfDay(),
            'YTD'     => $date->startOfYear()->startOfDay(),
            default   => null,
        };
        if (null !== $result) {
            Log::debug(sprintf('Result is "%s"', $date->toIso8601String()));

            return $result;
        }

        if ('custom' === $repeatFreq) {
            Log::debug(sprintf('Custom, result is "%s"', $date->toIso8601String()));

            return $date; // the date is already at the start.
        }
        Log::error(sprintf('Cannot do startOfPeriod for $repeat_freq "%s"', $repeatFreq));

        return $theDate;
    }

    public function endOfPeriod(Carbon $end, string $repeatFreq): Carbon
    {
        $currentEnd = clone $end;
        Log::debug(sprintf('Now in endOfPeriod("%s", "%s").', $currentEnd->toIso8601String(), $repeatFreq));

        $functionMap = [
            '1D'        => 'endOfDay',
            'daily'     => 'endOfDay',
            '1W'        => 'addWeek',
            'week'      => 'addWeek',
            'weekly'    => 'addWeek',
            '1M'        => 'addMonth',
            'month'     => 'addMonth',
            'monthly'   => 'addMonth',
            '3M'        => 'addQuarter',
            'quarter'   => 'addQuarter',
            'quarterly' => 'addQuarter',
            '6M'        => 'addMonths',
            'half-year' => 'addMonths',
            'half_year' => 'addMonths',
            'year'      => 'addYear',
            'yearly'    => 'addYear',
            '1Y'        => 'addYear',
        ];
        $modifierMap = ['half-year' => 6, 'half_year' => 6, '6M' => 6];
        $subDay      = ['week', 'weekly', '1W', 'month', 'monthly', '1M', '3M', 'quarter', 'quarterly', '6M', 'half-year', 'half_year', '1Y', 'year', 'yearly'];

        if ('custom' === $repeatFreq) {
            // if the repeat frequency is "custom", use the current session start/end to see how large the range is,
            // and use that to "add" another period.
            // if there is no session data available use "30 days" as a default.
            $diffInDays = 30;
            if (null !== session('start') && null !== session('end')) {
                Log::debug('Session data available.');

                /** @var Carbon $tStart */
                $tStart = session('start', today(config('app.timezone'))->startOfMonth());

                /** @var Carbon $tEnd */
                $tEnd       = session('end', today(config('app.timezone'))->endOfMonth());
                $diffInDays = (int) $tStart->diffInDays($tEnd, true);
            }
            Log::debug(sprintf('Diff in days is %d', $diffInDays));
            $currentEnd->addDays($diffInDays);

            return $currentEnd;
        }
        if('MTD' === $repeatFreq) {
            $today = today();
            if($today->isSameMonth($end)) {
                return $today->endOfDay();
            }
            return $end->endOfMonth();
        }

        $result = match ($repeatFreq) {
            'last7'   => $currentEnd->addDays(7)->startOfDay(),
            'last30'  => $currentEnd->addDays(30)->startOfDay(),
            'last90'  => $currentEnd->addDays(90)->startOfDay(),
            'last365' => $currentEnd->addDays(365)->startOfDay(),
            'MTD'     => $currentEnd->startOfMonth()->startOfDay(),
            'QTD'     => $currentEnd->firstOfQuarter()->startOfDay(),
            'YTD'     => $currentEnd->startOfYear()->startOfDay(),
            default   => null,
        };
        if (null !== $result) {
            return $result;
        }
        unset($result);

        if (!array_key_exists($repeatFreq, $functionMap)) {
            Log::error(sprintf('Cannot do endOfPeriod for $repeat_freq "%s"', $repeatFreq));

            return $end;
        }
        $function = $functionMap[$repeatFreq];

        if (array_key_exists($repeatFreq, $modifierMap)) {
            $currentEnd->{$function}($modifierMap[$repeatFreq]); // @phpstan-ignore-line
            if (in_array($repeatFreq, $subDay, true)) {
                $currentEnd->subDay();
            }
            $currentEnd->endOfDay();

            return $currentEnd;
        }
        $currentEnd->{$function}(); // @phpstan-ignore-line
        $currentEnd->endOfDay();
        if (in_array($repeatFreq, $subDay, true)) {
            $currentEnd->subDay();
        }
        Log::debug(sprintf('Final result: %s', $currentEnd->toIso8601String()));

        return $currentEnd;
    }

    public function daysUntilEndOfMonth(Carbon $date): int
    {
        $endOfMonth = $date->copy()->endOfMonth();

        return (int) $date->diffInDays($endOfMonth, true);
    }

    public function diffInPeriods(string $period, int $skip, Carbon $beginning, Carbon $end): int
    {
        Log::debug(sprintf(
                       'diffInPeriods: %s (skip: %d), between %s and %s.',
                       $period,
                       $skip,
                       $beginning->format('Y-m-d'),
                       $end->format('Y-m-d')
                   ));
        $map = [
            'daily'     => 'diffInDays',
            'weekly'    => 'diffInWeeks',
            'monthly'   => 'diffInMonths',
            'quarterly' => 'diffInMonths',
            'half-year' => 'diffInMonths',
            'yearly'    => 'diffInYears',
        ];
        if (!array_key_exists($period, $map)) {
            Log::warning(sprintf('No diffInPeriods for period "%s"', $period));

            return 1;
        }
        $func = $map[$period];
        // first do the diff
        $floatDiff = $beginning->{$func}($end, true); // @phpstan-ignore-line

        // then correct for quarterly or half-year
        if ('quarterly' === $period) {
            Log::debug(sprintf('Q: Corrected %f to %f', $floatDiff, $floatDiff / 3));
            $floatDiff /= 3;
        }
        if ('half-year' === $period) {
            Log::debug(sprintf('H: Corrected %f to %f', $floatDiff, $floatDiff / 6));
            $floatDiff /= 6;
        }

        // then do ceil()
        $diff = ceil($floatDiff);

        Log::debug(sprintf('Diff is %f periods (%d rounded up)', $floatDiff, $diff));

        if ($skip > 0) {
            $parameter = $skip + 1;
            $diff      = ceil($diff / $parameter) * $parameter;
            Log::debug(sprintf(
                           'diffInPeriods: skip is %d, so param is %d, and diff becomes %d',
                           $skip,
                           $parameter,
                           $diff
                       ));
        }

        return (int) $diff;
    }

    public function endOfX(Carbon $theCurrentEnd, string $repeatFreq, ?Carbon $maxDate): Carbon
    {
        $functionMap = [
            '1D'        => 'endOfDay',
            'daily'     => 'endOfDay',
            '1W'        => 'endOfWeek',
            'week'      => 'endOfWeek',
            'weekly'    => 'endOfWeek',
            'month'     => 'endOfMonth',
            '1M'        => 'endOfMonth',
            'monthly'   => 'endOfMonth',
            '3M'        => 'lastOfQuarter',
            'quarter'   => 'lastOfQuarter',
            'quarterly' => 'lastOfQuarter',
            '1Y'        => 'endOfYear',
            'year'      => 'endOfYear',
            'yearly'    => 'endOfYear',
        ];

        $currentEnd = clone $theCurrentEnd;

        if (array_key_exists($repeatFreq, $functionMap)) {
            $function = $functionMap[$repeatFreq];
            $currentEnd->{$function}(); // @phpstan-ignore-line
        }

        if (null !== $maxDate && $currentEnd > $maxDate) {
            return clone $maxDate;
        }

        return $currentEnd;
    }

    /**
     * Returns the user's view range and if necessary, corrects the dynamic view
     * range to a normal range.
     */
    public function getViewRange(bool $correct): string
    {
        $range = app('preferences')->get('viewRange', '1M')?->data ?? '1M';
        if (is_array($range)) {
            $range = '1M';
        }
        $range = (string) $range;
        if (!$correct) {
            return $range;
        }

        switch ($range) {
            default:
                return $range;

            case 'last7':
                return '1W';

            case 'last30':
            case 'MTD':
                return '1M';

            case 'last90':
            case 'QTD':
                return '3M';

            case 'last365':
            case 'YTD':
                return '1Y';
        }
    }

    /**
     * @throws FireflyException
     */
    public function listOfPeriods(Carbon $start, Carbon $end): array
    {
        $locale = app('steam')->getLocale();
        // define period to increment
        $increment     = 'addDay';
        $format        = $this->preferredCarbonFormat($start, $end);
        $displayFormat = (string) trans('config.month_and_day_js', [], $locale);
        $diff          = $start->diffInMonths($end, true);
        // increment by month (for year)
        if ($diff >= 1.0001) {
            $increment     = 'addMonth';
            $displayFormat = (string) trans('config.month_js');
        }

        // increment by year (for multi-year)
        if ($diff >= 12.0001) {
            $increment     = 'addYear';
            $displayFormat = (string) trans('config.year_js');
        }
        $begin   = clone $start;
        $entries = [];
        while ($begin < $end) {
            $formatted           = $begin->format($format);
            $displayed           = $begin->isoFormat($displayFormat);
            $entries[$formatted] = $displayed;
            $begin->{$increment}(); // @phpstan-ignore-line
        }

        return $entries;
    }

    /**
     * If the date difference between start and end is less than a month, method returns "Y-m-d". If the difference is
     * less than a year, method returns "Y-m". If the date difference is larger, method returns "Y".
     */
    public function preferredCarbonFormat(Carbon $start, Carbon $end): string
    {
        $format = 'Y-m-d';
        $diff   = $start->diffInMonths($end, true);
        Log::debug(sprintf('preferredCarbonFormat(%s, %s) = %f', $start->format('Y-m-d'), $end->format('Y-m-d'), $diff));
        if ($diff >= 1.001) {
            Log::debug(sprintf('Return Y-m because %s', $diff));
            $format = 'Y-m';
        }

        if ($diff >= 12.001) {
            Log::debug(sprintf('Return Y because %s', $diff));
            $format = 'Y';
        }

        return $format;
    }

    public function periodShow(Carbon $theDate, string $repeatFrequency): string
    {
        $date      = clone $theDate;
        $formatMap = [
            '1D'      => (string) trans('config.specific_day_js'),
            'daily'   => (string) trans('config.specific_day_js'),
            'custom'  => (string) trans('config.specific_day_js'),
            '1W'      => (string) trans('config.week_in_year_js'),
            'week'    => (string) trans('config.week_in_year_js'),
            'weekly'  => (string) trans('config.week_in_year_js'),
            '1M'      => (string) trans('config.month_js'),
            'month'   => (string) trans('config.month_js'),
            'monthly' => (string) trans('config.month_js'),
            '1Y'      => (string) trans('config.year_js'),
            'year'    => (string) trans('config.year_js'),
            'yearly'  => (string) trans('config.year_js'),
            '6M'      => (string) trans('config.half_year_js'),
        ];

        if (array_key_exists($repeatFrequency, $formatMap)) {
            return $date->isoFormat($formatMap[$repeatFrequency]);
        }
        if ('3M' === $repeatFrequency || 'quarter' === $repeatFrequency) {
            $quarter = ceil($theDate->month / 3);

            return sprintf('Q%d %d', $quarter, $theDate->year);
        }

        // special formatter for quarter of year
        Log::error(sprintf('No date formats for frequency "%s"!', $repeatFrequency));

        return $date->format('Y-m-d');
    }

    /**
     * Same as preferredCarbonFormat but by string
     */
    public function preferredCarbonFormatByPeriod(string $period): string
    {
        return match ($period) {
            default    => 'Y-m-d',
            // '1D'    => 'Y-m-d',
            '1W'       => '\WW,Y',
            '1M'       => 'Y-m',
            '3M', '6M' => '\QQ,Y',
            '1Y'       => 'Y',
        };
    }

    /**
     * If the date difference between start and end is less than a month, method returns trans(config.month_and_day).
     * If the difference is less than a year, method returns "config.month". If the date difference is larger, method
     * returns "config.year".
     */
    public function preferredCarbonLocalizedFormat(Carbon $start, Carbon $end): string
    {
        $locale = app('steam')->getLocale();
        $format = (string) trans('config.month_and_day_js', [], $locale);
        if ($start->diffInMonths($end, true) > 1) {
            $format = (string) trans('config.month_js', [], $locale);
        }

        if ($start->diffInMonths($end, true) > 12) {
            $format = (string) trans('config.year_js', [], $locale);
        }

        return $format;
    }

    /**
     * If the date difference between start and end is less than a month, method returns "endOfDay". If the difference
     * is less than a year, method returns "endOfMonth". If the date difference is larger, method returns "endOfYear".
     */
    public function preferredEndOfPeriod(Carbon $start, Carbon $end): string
    {
        $format = 'endOfDay';
        if ((int) $start->diffInMonths($end, true) > 1) {
            $format = 'endOfMonth';
        }

        if ((int) $start->diffInMonths($end, true) > 12) {
            $format = 'endOfYear';
        }

        return $format;
    }

    /**
     * If the date difference between start and end is less than a month, method returns "1D". If the difference is
     * less than a year, method returns "1M". If the date difference is larger, method returns "1Y".
     */
    public function preferredRangeFormat(Carbon $start, Carbon $end): string
    {
        $format = '1D';
        if ((int) $start->diffInMonths($end, true) > 1) {
            $format = '1M';
        }

        if ((int) $start->diffInMonths($end, true) > 12) {
            $format = '1Y';
        }

        return $format;
    }

    /**
     * If the date difference between start and end is less than a month, method returns "%Y-%m-%d". If the difference
     * is less than a year, method returns "%Y-%m". If the date difference is larger, method returns "%Y".
     */
    public function preferredSqlFormat(Carbon $start, Carbon $end): string
    {
        $format = '%Y-%m-%d';
        if ((int) $start->diffInMonths($end, true) > 1) {
            $format = '%Y-%m';
        }

        if ((int) $start->diffInMonths($end, true) > 12) {
            $format = '%Y';
        }

        return $format;
    }

    /**
     * @throws FireflyException
     */
    public function subtractPeriod(Carbon $theDate, string $repeatFreq, ?int $subtract = null): Carbon
    {
        $subtract ??= 1;
        $date     = clone $theDate;
        // 1D 1W 1M 3M 6M 1Y
        $functionMap = [
            '1D'      => 'subDays',
            'daily'   => 'subDays',
            'week'    => 'subWeeks',
            '1W'      => 'subWeeks',
            'weekly'  => 'subWeeks',
            'month'   => 'subMonths',
            '1M'      => 'subMonths',
            'monthly' => 'subMonths',
            'year'    => 'subYears',
            '1Y'      => 'subYears',
            'yearly'  => 'subYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            '3M'        => 3,
            'quarterly' => 3,
            'half-year' => 6,
            '6M'        => 6,
        ];
        if (array_key_exists($repeatFreq, $functionMap)) {
            $function = $functionMap[$repeatFreq];
            $date->{$function}($subtract); // @phpstan-ignore-line

            return $date;
        }
        if (array_key_exists($repeatFreq, $modifierMap)) {
            $subtract *= $modifierMap[$repeatFreq];
            $date->subMonths($subtract);

            return $date;
        }
        // a custom range requires the session start
        // and session end to calculate the difference in days.
        // this is then subtracted from $theDate (* $subtract).
        if ('custom' === $repeatFreq) {
            /** @var Carbon $tStart */
            $tStart = session('start', today(config('app.timezone'))->startOfMonth());

            /** @var Carbon $tEnd */
            $tEnd       = session('end', today(config('app.timezone'))->endOfMonth());
            $diffInDays = (int) $tStart->diffInDays($tEnd, true);
            $date->subDays($diffInDays * $subtract);

            return $date;
        }

        switch ($repeatFreq) {
            default:
                break;

            case 'last7':
                $date->subDays(7);

                return $date;

            case 'last30':
                $date->subDays(30);

                return $date;

            case 'last90':
                $date->subDays(90);

                return $date;

            case 'last365':
                $date->subDays(365);

                return $date;

            case 'YTD':
                $date->subYear();

                return $date;

            case 'QTD':
                $date->subQuarter();

                return $date;

            case 'MTD':
                $date->subMonth();

                return $date;
        }

        throw new FireflyException(sprintf('Cannot do subtractPeriod for $repeat_freq "%s"', $repeatFreq));
    }

    /**
     * @throws FireflyException
     */
    public function updateEndDate(string $range, Carbon $start): Carbon
    {
        Log::debug(sprintf('updateEndDate("%s", "%s")', $range, $start->format('Y-m-d')));
        $functionMap = [
            '1D'     => 'endOfDay',
            '1W'     => 'endOfWeek',
            '1M'     => 'endOfMonth',
            '3M'     => 'lastOfQuarter',
            'custom' => 'startOfMonth', // this only happens in test situations.
        ];
        $end         = clone $start;

        if (array_key_exists($range, $functionMap)) {
            $function = $functionMap[$range];
            $end->{$function}(); // @phpstan-ignore-line

            Log::debug(sprintf('updateEndDate returns "%s"', $end->format('Y-m-d')));

            return $end;
        }
        if ('6M' === $range) {
            if ($start->month >= 7) {
                $end->endOfYear();

                return $end;
            }
            $end->startOfYear()->addMonths(6);

            return $end;
        }

        // make sure 1Y takes the fiscal year into account.
        if ('1Y' === $range) {
            /** @var FiscalHelperInterface $fiscalHelper */
            $fiscalHelper = app(FiscalHelperInterface::class);

            return $fiscalHelper->endOfFiscalYear($end);
        }
        $list = [
            'last7',
            'last30',
            'last90',
            'last365',
            'YTD',
            'QTD',
            'MTD',
        ];
        if (in_array($range, $list, true)) {
            $end = today(config('app.timezone'));
            $end->endOfDay();
            Log::debug(sprintf('updateEndDate returns "%s"', $end->format('Y-m-d')));

            return $end;
        }

        throw new FireflyException(sprintf('updateEndDate cannot handle range "%s"', $range));
    }

    /**
     * @throws FireflyException
     */
    public function updateStartDate(string $range, Carbon $start): Carbon
    {
        Log::debug(sprintf('updateStartDate("%s", "%s")', $range, $start->format('Y-m-d')));
        $functionMap = [
            '1D'     => 'startOfDay',
            '1W'     => 'startOfWeek',
            '1M'     => 'startOfMonth',
            '3M'     => 'firstOfQuarter',
            'custom' => 'startOfMonth', // this only happens in test situations.
        ];
        if (array_key_exists($range, $functionMap)) {
            $function = $functionMap[$range];
            $start->{$function}(); // @phpstan-ignore-line
            Log::debug(sprintf('updateStartDate returns "%s"', $start->format('Y-m-d')));

            return $start;
        }
        if ('6M' === $range) {
            if ($start->month >= 7) {
                $start->startOfYear()->addMonths(6);

                return $start;
            }
            $start->startOfYear();

            return $start;
        }

        // make sure 1Y takes the fiscal year into account.
        if ('1Y' === $range) {
            /** @var FiscalHelperInterface $fiscalHelper */
            $fiscalHelper = app(FiscalHelperInterface::class);

            return $fiscalHelper->startOfFiscalYear($start);
        }

        switch ($range) {
            default:
                break;

            case 'last7':
                $start->subDays(7);

                return $start;

            case 'last30':
                $start->subDays(30);

                return $start;

            case 'last90':
                $start->subDays(90);

                return $start;

            case 'last365':
                $start->subDays(365);

                return $start;

            case 'YTD':
                $start->startOfYear();

                return $start;

            case 'QTD':
                $start->startOfQuarter();

                return $start;

            case 'MTD':
                $start->startOfMonth();

                return $start;
        }

        throw new FireflyException(sprintf('updateStartDate cannot handle range "%s"', $range));
    }
}
