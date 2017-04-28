<?php
/**
 * TransactionController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Response;
use Steam;
use View;

/**
 * Class TransactionController
 *
 * @package FireflyIII\Http\Controllers
 */
class TransactionController extends Controller
{
    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.transactions'));
                View::share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );

    }

    /**
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     * @param string                     $what
     *
     * @param string                     $moment
     *
     * @return View
     */
    public function index(Request $request, JournalRepositoryInterface $repository, string $what, string $moment = '')
    {
        // default values:
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $page         = intval($request->get('page')) == 0 ? 1 : intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $count        = 0;
        $loop         = 0;
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        // prep for "all" view.
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_' . $what);
            $first    = $repository->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $subTitle = trans(
                'firefly.title_' . $what . '_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $periods  = $this->getPeriodOverview($what);
        }

        // prep for current period
        if (strlen($moment) === 0) {
            $start    = clone session('start', Navigation::startOfPeriod(new Carbon, $range));
            $end      = clone session('end', Navigation::endOfPeriod(new Carbon, $range));
            $periods  = $this->getPeriodOverview($what);
            $subTitle = trans(
                'firefly.title_' . $what . '_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }
        // grab journals, but be prepared to jump a period back to get the right ones:
        Log::info('Now at transaction loop start.');
        while ($count === 0 && $loop < 3) {
            $loop++;
            Log::info('Count is zero, search for journals.');
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount();
            $collector->removeFilter(InternalTransferFilter::class);
            $journals = $collector->getPaginatedJournals();
            $journals->setPath('/budgets/list/no-budget');
            $count = $journals->getCollection()->count();
            if ($count === 0) {
                $start->subDay();
                $start = Navigation::startOfPeriod($start, $range);
                $end   = Navigation::endOfPeriod($start, $range);
                Log::info(sprintf('Count is still zero, go back in time to "%s" and "%s"!', $start->format('Y-m-d'), $end->format('Y-m-d')));
            }
        }

        if ($moment != 'all' && $loop > 1) {
            $subTitle = trans(
                'firefly.title_' . $what . '_between',
                ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
            );
        }

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals', 'periods', 'start', 'end', 'moment'));

    }

    /**
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request, JournalRepositoryInterface $repository)
    {
        $ids  = $request->get('items');
        $date = new Carbon($request->get('date'));
        if (count($ids) > 0) {
            $order = 0;
            $ids   = array_unique($ids);
            foreach ($ids as $id) {
                $journal = $repository->find(intval($id));
                if ($journal && $journal->date->isSameDay($date)) {
                    $repository->setOrder($journal, $order);
                    $order++;
                }
            }
        }
        Preferences::mark();

        return Response::json([true]);

    }

    /**
     * @param TransactionJournal     $journal
     * @param JournalTaskerInterface $tasker
     *
     * @return View
     */
    public function show(TransactionJournal $journal, JournalTaskerInterface $tasker)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $events           = $tasker->getPiggyBankEvents($journal);
        $transactions     = $tasker->getTransactionsOverview($journal);
        $what             = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle         = trans('firefly.' . $what) . ' "' . e($journal->description) . '"';
        $foreignCurrency = null;

        if ($journal->hasMeta('foreign_currency_id')) {
            // @codeCoverageIgnoreStart
            /** @var CurrencyRepositoryInterface $repository */
            $repository      = app(CurrencyRepositoryInterface::class);
            $foreignCurrency = $repository->find(intval($journal->getMeta('foreign_currency_id')));
            // @codeCoverageIgnoreEnd
        }

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what', 'transactions', 'foreignCurrency'));


    }

    /**
     * @param string $what
     *
     * @return Collection
     * @throws FireflyException
     */
    private function getPeriodOverview(string $what): Collection
    {
        $repository = app(JournalRepositoryInterface::class);
        $first      = $repository->first();
        $start      = $first->date ?? new Carbon;
        $range      = Preferences::get('viewRange', '1M')->data;
        $start      = Navigation::startOfPeriod($start, $range);
        $end        = Navigation::endOfX(new Carbon, $range);
        $entries    = new Collection;
        $types      = config('firefly.transactionTypesByWhat.' . $what);

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($what);
        $cache->addProperty('transaction-list-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        Log::debug(sprintf('Going to get period expenses and incomes between %s and %s.', $start->format('Y-m-d'), $end->format('Y-m-d')));
        while ($end >= $start) {
            Log::debug('Loop start!');
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);

            // count journals without budget in this period:
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($end, $currentEnd)->withOpposingAccount()->setTypes($types);
            $collector->removeFilter(InternalTransferFilter::class);
            $set      = $collector->getJournals();
            $sum      = $set->sum('transaction_amount');
            $journals = $set->count();
            $dateStr  = $end->format('Y-m-d');
            $dateName = Navigation::periodShow($end, $range);
            $array    = [
                'string'      => $dateStr,
                'name'        => $dateName,
                'count'       => $journals,
                'spent'       => 0,
                'earned'      => 0,
                'transferred' => 0,
                'date'        => clone $end,
            ];
            Log::debug(sprintf('What is %s', $what));
            switch ($what) {
                case 'withdrawal':
                    $array['spent'] = $sum;
                    break;
                case 'deposit':
                    $array['earned'] = $sum;
                    break;
                case 'transfers':
                case 'transfer':
                    $array['transferred'] = Steam::positive($sum);
                    break;

            }
            $entries->push($array);
            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        Log::debug('End of loop');
        $cache->store($entries);

        return $entries;
    }

}
