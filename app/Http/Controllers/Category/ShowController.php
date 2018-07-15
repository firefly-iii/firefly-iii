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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;


/**
 *
 * Class ShowController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowController extends Controller
{

    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var JournalRepositoryInterface */
    private $journalRepos;
    /** @var CategoryRepositoryInterface */
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
     * @param Request     $request
     * @param Category    $category
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, Category $category, Carbon $start = null, Carbon $end = null)
    {
        /** @var Carbon $start */
        $start = $start ?? session('start');
        /** @var Carbon $end */
        $end          = $end ?? session('end');
        $subTitleIcon = 'fa-bar-chart';
        $moment       = '';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $periods      = $this->getPeriodOverview($category, $start);
        $path         = route('categories.show', [$category->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_category',
            ['name' => $category->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
             'end'  => $end->formatLocalized($this->monthAndDayFormat),]
        );

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        return view('categories.show', compact('category', 'transactions', 'moment', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
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
        $moment       = 'all';

        $subTitle = (string)trans('firefly.all_journals_for_category', ['name' => $category->name]);
        $first    = $this->repository->firstUseDate($category);
        /** @var Carbon $start */
        $start = $first ?? new Carbon;
        $end   = new Carbon;
        $path  = route('categories.show-all', [$category->id]);


        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->setCategory($category)->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        return view('categories.show', compact('category', 'moment', 'transactions', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * @param Category $category
     *
     * @param Carbon   $date
     *
     * @return Collection
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getPeriodOverview(Category $category, Carbon $date): Collection
    {
        $range    = app('preferences')->get('viewRange', '1M')->data;
        $first    = $this->journalRepos->firstNull();
        $start    = null === $first ? new Carbon : $first->date;
        $end      = $date ?? new Carbon;
        $accounts = $this->accountRepos->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($range);
        $cache->addProperty('categories.entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $currentDate) {
            $spent  = $this->repository->spentInPeriod(new Collection([$category]), $accounts, $currentDate['start'], $currentDate['end']);
            $earned = $this->repository->earnedInPeriod(new Collection([$category]), $accounts, $currentDate['start'], $currentDate['end']);
            /** @noinspection PhpUndefinedMethodInspection */
            $dateStr  = $currentDate['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);

            // amount transferred
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->setCategory($category)
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = app('steam')->positive((string)$collector->getJournals()->sum('transaction_amount'));

            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'sum'         => bcadd($earned, $spent),
                    'transferred' => $transferred,
                    'start'       => clone $currentDate['start'],
                    'end'         => clone $currentDate['end'],
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }

}