<?php
/**
 * ShowController.php
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
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class ShowController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowController extends Controller
{
    use PeriodOverview;
    /** @var AccountRepositoryInterface The account repository */
    private $accountRepos;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $journalRepos;
    /** @var CategoryRepositoryInterface The category repository */
    private $repository;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.categories'));
                app('view')->share('mainTitleIcon', 'fa-bar-chart');
                $this->journalRepos = app(JournalRepositoryInterface::class);
                $this->repository   = app(CategoryRepositoryInterface::class);
                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Show a single category.
     *
     * @param Request     $request
     * @param Category    $category
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Category $category, Carbon $start = null, Carbon $end = null)
    {
        Log::debug('Now in show()');
        /** @var Carbon $start */
        $start = $start ?? session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end          = $end ?? session('end', Carbon::now()->endOfMonth());
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $periods      = $this->getCategoryPeriodOverview($category, $end);
        $path         = route('categories.show', [$category->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_category',
            ['name' => $category->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
             'end'  => $end->formatLocalized($this->monthAndDayFormat),]
        );

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        Log::debug('End of show()');

        return view('categories.show', compact('category', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * Show all transactions within a category.
     *
     * @param Request  $request
     * @param Category $category
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showAll(Request $request, Category $category)
    {
        // default values:
        $subTitleIcon = 'fa-bar-chart';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        $subTitle = (string)trans('firefly.all_journals_for_category', ['name' => $category->name]);
        $first    = $this->repository->firstUseDate($category);
        /** @var Carbon $start */
        $start = $first ?? new Carbon;
        $end   = new Carbon;
        $path  = route('categories.show.all', [$category->id]);


        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath($path);

        return view('categories.show', compact('category', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }
}
