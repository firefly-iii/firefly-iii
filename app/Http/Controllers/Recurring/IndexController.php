<?php
/**
 * IndexController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Recurring;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{
    use GetConfigurationData;
    /** @var RecurringRepositoryInterface Recurring repository */
    private $recurring;

    /**
     * IndexController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string)trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * TODO the notes of a recurrence are pretty pointless at this moment.
     * Show all recurring transactions.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \FireflyIII\Exceptions\FireflyException
     *
     */
    public function index(Request $request)
    {
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->recurring->get();
        $today      = new Carbon;
        $year       = new Carbon;
        $year->addYear();

        // split collection
        $total = $collection->count();
        /** @var Collection $recurrences */
        $recurrences = $collection->slice(($page - 1) * $pageSize, $pageSize);

        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $recurring = [];
        /** @var Recurrence $recurrence */
        foreach ($recurrences as $recurrence) {
            $array                 = $transformer->transform($recurrence);
            $array['first_date']   = new Carbon($array['first_date']);
            $array['repeat_until'] = null === $array['repeat_until'] ? null : new Carbon($array['repeat_until']);
            $array['latest_date']  = null === $array['latest_date'] ? null : new Carbon($array['latest_date']);
            $array['occurrences']  = array_slice($this->recurring->getOccurrencesInRange($recurrence->recurrenceRepetitions->first(), $today, $year),0,1);
            $recurring[]           = $array;
        }
        $paginator = new LengthAwarePaginator($recurring, $total, $pageSize, $page);
        $paginator->setPath(route('recurring.index'));

        $this->verifyRecurringCronJob();

        return view('recurring.index', compact('paginator', 'page', 'pageSize', 'total'));
    }

}
