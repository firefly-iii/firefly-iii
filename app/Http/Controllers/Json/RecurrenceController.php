<?php

/**
 * RecurrenceController.php
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

namespace FireflyIII\Http\Controllers\Json;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RecurrenceController
 */
class RecurrenceController extends Controller
{
    private RecurringRepositoryInterface $recurring;

    /**
     * RecurrenceController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows all events for a repetition. Used in calendar.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @throws FireflyException
     */
    public function events(Request $request): JsonResponse
    {
        $occurrences                   = [];
        $return                        = [];
        $start                         = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end                           = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $firstDate                     = Carbon::createFromFormat('Y-m-d', $request->get('first_date'));
        $endDate                       = '' !== (string) $request->get('end_date') ? Carbon::createFromFormat('Y-m-d', $request->get('end_date')) : null;
        $endsAt                        = (string) $request->get('ends');
        $repetitionType                = explode(',', $request->get('type'))[0];
        $repetitions                   = (int) $request->get('reps');
        $weekend                       = (int) $request->get('weekend');
        $repetitionMoment              = '';
        $skip                          = (int) $request->get('skip');
        $skip                          = $skip < 0 || $skip > 31 ? 0 : $skip;
        $weekend                       = $weekend < 1 || $weekend > 4 ? 1 : $weekend;

        if (null === $endDate) {
            // safety catch:
            $endDate = now()->addYear();
        }

        if (null === $start || null === $end || null === $firstDate) {
            return response()->json();
        }

        $start->startOfDay();

        // if $firstDate is beyond $end, simply return an empty array.
        if ($firstDate->gt($end)) {
            return response()->json();
        }
        // if $firstDate is beyond start, use that one:
        $actualStart                   = clone $firstDate;

        if ('weekly' === $repetitionType || 'monthly' === $repetitionType) {
            $repetitionMoment = explode(',', $request->get('type'))[1] ?? '1';
        }
        if ('ndom' === $repetitionType) {
            $repetitionMoment = str_ireplace('ndom,', '', $request->get('type'));
        }
        if ('yearly' === $repetitionType) {
            $repetitionMoment = explode(',', $request->get('type'))[1] ?? '2018-01-01';
        }
        $actualStart->startOfDay();
        $repetition                    = new RecurrenceRepetition();
        $repetition->repetition_type   = $repetitionType;
        $repetition->repetition_moment = $repetitionMoment;
        $repetition->repetition_skip   = $skip;
        $repetition->weekend           = $weekend;
        $actualEnd                     = clone $end;

        if ('until_date' === $endsAt) {
            $actualEnd   = $endDate;
            $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
        }
        if ('times' === $endsAt) {
            $occurrences = $this->recurring->getXOccurrences($repetition, $actualStart, $repetitions);
        }
        if ('times' !== $endsAt && 'until_date' !== $endsAt) {
            // 'forever'
            $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
        }

        /** @var Carbon $current */
        foreach ($occurrences as $current) {
            if ($current->gte($start)) {
                $event    = [
                    'id'        => $repetitionType.$firstDate->format('Ymd'),
                    'title'     => 'X',
                    'allDay'    => true,
                    'start'     => $current->format('Y-m-d'),
                    'end'       => $current->format('Y-m-d'),
                    'editable'  => false,
                    'rendering' => 'background',
                ];
                $return[] = $event;
            }
        }

        return response()->json($return);
    }

    /**
     * Suggests repetition moments.
     */
    public function suggest(Request $request): JsonResponse
    {
        $string      = '' === (string) $request->get('date') ? date('Y-m-d') : (string) $request->get('date');
        $today       = today(config('app.timezone'))->startOfDay();

        try {
            $date = Carbon::createFromFormat('Y-m-d', $string, config('app.timezone'));
        } catch (InvalidFormatException $e) {
            $date = Carbon::today(config('app.timezone'));
        }
        if (null === $date) {
            return response()->json();
        }
        $date->startOfDay();
        $preSelected = (string) $request->get('pre_select');
        $locale      = app('steam')->getLocale();

        app('log')->debug(sprintf('date = %s, today = %s. date > today? %s', $date->toAtomString(), $today->toAtomString(), var_export($date > $today, true)));
        app('log')->debug(sprintf('past = true? %s', var_export('true' === (string) $request->get('past'), true)));

        $result      = [];
        if ($date > $today || 'true' === (string) $request->get('past')) {
            app('log')->debug('Will fill dropdown.');
            $weekly     = sprintf('weekly,%s', $date->dayOfWeekIso);
            $monthly    = sprintf('monthly,%s', $date->day);
            $dayOfWeek  = (string) trans(sprintf('config.dow_%s', $date->dayOfWeekIso));
            $ndom       = sprintf('ndom,%s,%s', $date->weekOfMonth, $date->dayOfWeekIso);
            $yearly     = sprintf('yearly,%s', $date->format('Y-m-d'));
            $yearlyDate = $date->isoFormat((string) trans('config.month_and_day_no_year_js', [], $locale));
            $result     = [
                'daily'  => ['label' => (string) trans('firefly.recurring_daily'), 'selected' => str_starts_with($preSelected, 'daily')],
                $weekly  => [
                    'label'    => (string) trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek]),
                    'selected' => str_starts_with($preSelected, 'weekly'),
                ],
                $monthly => [
                    'label'    => (string) trans('firefly.recurring_monthly', ['dayOfMonth' => $date->day]),
                    'selected' => str_starts_with($preSelected, 'monthly'),
                ],
                $ndom    => [
                    'label'    => (string) trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $date->weekOfMonth]),
                    'selected' => str_starts_with($preSelected, 'ndom'),
                ],
                $yearly  => [
                    'label'    => (string) trans('firefly.recurring_yearly', ['date' => $yearlyDate]),
                    'selected' => str_starts_with($preSelected, 'yearly'),
                ],
            ];
        }
        app('log')->debug('Dropdown is', $result);

        return response()->json($result);
    }
}
