<?php
/**
 * NoCategoryController.php
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

namespace FireflyIII\Http\Controllers\Category;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class NoCategoryController
 */
class NoCategoryController extends Controller
{
    use PeriodOverview;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $journalRepos;

    /**
     * CategoryController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showBudget', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bar-chart');
                $this->journalRepos = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show transactions without a category.
     *
     * @param Request $request
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Carbon $start = null, Carbon $end = null)
    {
        Log::debug('Start of noCategory()');
        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end      = $end ?? session('end');
        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        $subTitle = trans(
            'firefly.without_category_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );
        $periods  = $this->getNoCategoryPeriodOverview($start);

        Log::debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
                  ->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category'));

        return view('categories.no-category', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }


    /**
     * Show all transactions without a category.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showAll(Request $request)
    {
        // default values:
        $start    = null;
        $end      = null;
        $periods  = new Collection;
        $page     = (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        Log::debug('Start of noCategory()');
        $subTitle = (string)trans('firefly.all_journals_without_category');
        $first    = $this->journalRepos->firstNull();
        $start    = null === $first ? new Carbon : $first->date;
        $end      = new Carbon;
        Log::debug(sprintf('Start for noCategory() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for noCategory() is %s', $end->format('Y-m-d')));

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()
            ->withAccountInformation()->withBudgetInformation()
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $groups = $collector->getPaginatedGroups();
        $groups->setPath(route('categories.no-category.all'));

        return view('categories.no-category', compact('groups', 'subTitle', 'periods', 'start', 'end'));
    }
}
