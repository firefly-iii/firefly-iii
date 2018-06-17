<?php
/**
 * IndexController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Recurring;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{
    /** @var RecurringRepositoryInterface */
    private $recurring;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @throws FireflyException
     * @return JsonResponse
     */
    function events(Request $request): JsonResponse
    {
        $return           = [];
        $start            = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end              = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $firstDate        = Carbon::createFromFormat('Y-m-d', $request->get('first_date'));
        $endDate          = '' !== (string)$request->get('end_date') ? Carbon::createFromFormat('Y-m-d', $request->get('end_date')) : null;
        $endsAt           = (string)$request->get('ends');
        $repetitionType   = explode(',', $request->get('type'))[0];
        $repetitions      = (int)$request->get('reps');
        $repetitionMoment = '';
        $start->startOfDay();

        // if $firstDate is beyond $end, simply return an empty array.
        if ($firstDate->gt($end)) {
            return Response::json([]);
        }
        // if $firstDate is beyond start, use that one:
        $actualStart = clone $firstDate;

        switch ($repetitionType) {
            default:
                throw new FireflyException(sprintf('Cannot handle repetition type "%s"', $repetitionType));
            case 'daily':
                break;
            case 'weekly':
            case 'monthly':
                $repetitionMoment = explode(',', $request->get('type'))[1] ?? '1';
                break;
            case 'ndom':
                $repetitionMoment = str_ireplace('ndom,', '', $request->get('type'));
                break;
            case 'yearly':
                $repetitionMoment = explode(',', $request->get('type'))[1] ?? '2018-01-01';
                break;
        }
        $repetition                    = new RecurrenceRepetition;
        $repetition->repetition_type   = $repetitionType;
        $repetition->repetition_moment = $repetitionMoment;
        $repetition->repetition_skip   = (int)$request->get('skip');

        $actualEnd = clone $end;
        switch ($endsAt) {
            default:
                throw new FireflyException(sprintf('Cannot generate events for type that ends at "%s".', $endsAt));
            case 'forever':
                // simply generate up until $end. No change from default behavior.
                $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
                break;
            case 'until_date':
                $actualEnd   = $endDate ?? clone $end;
                $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
                break;
            case 'times':
                $occurrences = $this->recurring->getXOccurrences($repetition, $actualStart, $repetitions);
                break;
        }


        /** @var Carbon $current */
        foreach ($occurrences as $current) {
            if ($current->gte($start)) {
                $event    = [
                    'id'        => $repetitionType . $firstDate->format('Ymd'),
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

        return Response::json($return);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function index(Request $request)
    {
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->recurring->getActive();

        // TODO: split collection into pages

        $transformer = new RecurrenceTransformer(new ParameterBag);
        $recurring   = [];
        /** @var Recurrence $recurrence */
        foreach ($collection as $recurrence) {
            $array                = $transformer->transform($recurrence);
            $array['first_date']  = new Carbon($array['first_date']);
            $array['latest_date'] = null === $array['latest_date'] ? null : new Carbon($array['latest_date']);
            $recurring[]          = $array;
        }

        return view('recurring.index', compact('recurring', 'page', 'pageSize'));
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function show(Recurrence $recurrence)
    {
        $transformer = new RecurrenceTransformer(new ParameterBag);
        $array       = $transformer->transform($recurrence);

        // transform dates back to Carbon objects:
        foreach ($array['recurrence_repetitions'] as $index => $repetition) {
            foreach ($repetition['occurrences'] as $item => $occurrence) {
                $array['recurrence_repetitions'][$index]['occurrences'][$item] = new Carbon($occurrence);
            }
        }

        $subTitle = trans('firefly.overview_for_recurrence', ['title' => $recurrence->title]);

        return view('recurring.show', compact('recurrence', 'subTitle', 'array'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function suggest(Request $request): JsonResponse
    {
        $today  = new Carbon;
        $date   = Carbon::createFromFormat('Y-m-d', $request->get('date'));
        $result = [];
        if ($date > $today || $request->get('past') === 'true') {
            $weekly     = sprintf('weekly,%s', $date->dayOfWeekIso);
            $monthly    = sprintf('monthly,%s', $date->day);
            $dayOfWeek  = trans(sprintf('config.dow_%s', $date->dayOfWeekIso));
            $ndom       = sprintf('ndom,%s,%s', $date->weekOfMonth, $date->dayOfWeekIso);
            $yearly     = sprintf('yearly,%s', $date->format('Y-m-d'));
            $yearlyDate = $date->formatLocalized(trans('config.month_and_day_no_year'));
            $result     = [
                'daily'  => trans('firefly.recurring_daily'),
                $weekly  => trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek]),
                $monthly => trans('firefly.recurring_monthly', ['dayOfMonth' => $date->day]),
                $ndom    => trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $date->weekOfMonth]),
                $yearly  => trans('firefly.recurring_yearly', ['date' => $yearlyDate]),
            ];
        }


        return Response::json($result);
    }

}