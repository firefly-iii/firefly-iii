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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
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
        foreach ($array['repetitions'] as $index => $repetition) {
            foreach ($repetition['occurrences'] as $item => $occurrence) {
                $array['repetitions'][$index]['occurrences'][$item] = new Carbon($occurrence);
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
        if ($date > $today) {
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