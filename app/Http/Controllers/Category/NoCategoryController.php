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
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class NoCategoryController
 */
class NoCategoryController extends Controller
{

    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $journalRepos;

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

                return $next($request);
            }
        );
    }

    /**
     * Show transactions without a category.
     *
     * @param Request     $request
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

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()->withOpposingAccount()
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('categories.no-category'));

        return view('categories.no-category', compact('transactions', 'subTitle', 'periods', 'start', 'end'));
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

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutCategory()->withOpposingAccount()
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedTransactions();
        $transactions->setPath(route('categories.no-category'));

        return view('categories.no-category', compact('transactions', 'subTitle', 'periods', 'start', 'end'));
    }


    /**
     * Show period overview for no category view.
     *
     * @param Carbon $theDate
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getNoCategoryPeriodOverview(Carbon $theDate): Collection // period overview method.
    {
        Log::debug(sprintf('Now in getNoCategoryPeriodOverview(%s)', $theDate->format('Y-m-d')));
        $range = app('preferences')->get('viewRange', '1M')->data;
        $first = $this->journalRepos->firstNull();
        $start = null === $first ? new Carbon : $first->date;
        $end   = $theDate ?? new Carbon;

        Log::debug(sprintf('Start for getNoCategoryPeriodOverview() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for getNoCategoryPeriodOverview() is %s', $end->format('Y-m-d')));

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-category-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $date) {

            // count journals without category in this period:
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $count = $collector->getTransactions()->count();

            // amount transferred
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = app('steam')->positive((string)$collector->getTransactions()->sum('transaction_amount'));

            // amount spent
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::WITHDRAWAL]
            );
            $spent = $collector->getTransactions()->sum('transaction_amount');

            // amount earned
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::DEPOSIT]
            );
            $earned = $collector->getTransactions()->sum('transaction_amount');
            /** @noinspection PhpUndefinedMethodInspection */
            $dateStr  = $date['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($date['end'], $date['period']);
            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'count'       => $count,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'transferred' => $transferred,
                    'start'       => clone $date['start'],
                    'end'         => clone $date['end'],
                ]
            );
        }
        Log::debug('End of loops');
        $cache->store($entries);

        return $entries;
    }
}
