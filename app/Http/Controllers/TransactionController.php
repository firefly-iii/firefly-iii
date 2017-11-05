<?php
/**
 * TransactionController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Response;
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
        $page         = intval($request->get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;
        $path         = route('transactions.index', [$what]);

        // prep for "all" view.
        if ($moment === 'all') {
            $subTitle = trans('firefly.all_' . $what);
            $first    = $repository->first();
            $start    = $first->date ?? new Carbon;
            $end      = new Carbon;
            $path     = route('transactions.index', [$what, 'all']);
        }

        // prep for "specific date" view.
        if (strlen($moment) > 0 && $moment !== 'all') {
            $start    = new Carbon($moment);
            $end      = Navigation::endOfPeriod($start, $range);
            $path     = route('transactions.index', [$what, $moment]);
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

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount();
        $collector->removeFilter(InternalTransferFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);


        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'transactions', 'periods', 'start', 'end', 'moment'));

    }

    /**
     * @param Request $request
     */
    public function reconcile(Request $request, JournalRepositoryInterface $repository)
    {
        $transactionIds = $request->get('transactions');
        foreach ($transactionIds as $transactionId) {
            $transactionId = intval($transactionId);
            $transaction   = $repository->findTransaction($transactionId);
            Log::debug(sprintf('Transaction ID is %d', $transaction->id));

            $repository->reconcile($transaction);
        }

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
     * @param TransactionJournal          $journal
     * @param JournalTaskerInterface      $tasker
     *
     * @param LinkTypeRepositoryInterface $linkTypeRepository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function show(TransactionJournal $journal, JournalTaskerInterface $tasker, LinkTypeRepositoryInterface $linkTypeRepository)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        $linkTypes    = $linkTypeRepository->get();
        $links        = $linkTypeRepository->getLinks($journal);
        $events       = $tasker->getPiggyBankEvents($journal);
        $transactions = $tasker->getTransactionsOverview($journal);
        $what         = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle     = trans('firefly.' . $what) . ' "' . $journal->description . '"';

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what', 'transactions', 'linkTypes', 'links'));
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
        $end        = Navigation::endOfX(new Carbon, $range, null);
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
            $journals = $collector->getJournals();
            $sum      = $journals->sum('transaction_amount');

            // count per currency:
            $sums     = $this->sumPerCurrency($journals);
            $dateStr  = $end->format('Y-m-d');
            $dateName = Navigation::periodShow($end, $range);
            $array    = [
                'string' => $dateStr,
                'name'   => $dateName,
                'sum'    => $sum,
                'sums'   => $sums,
                'date'   => clone $end,
            ];
            Log::debug(sprintf('What is %s', $what));
            if ($journals->count() > 0) {
                $entries->push($array);
            }
            $end = Navigation::subtractPeriod($end, $range, 1);
        }
        Log::debug('End of loop');
        $cache->store($entries);

        return $entries;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    private function sumPerCurrency(Collection $collection): array
    {
        $return = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $currencyId = $transaction->transaction_currency_id;

            // save currency information:
            if (!isset($return[$currencyId])) {
                $currencySymbol      = $transaction->transaction_currency_symbol;
                $decimalPlaces       = $transaction->transaction_currency_dp;
                $currencyCode        = $transaction->transaction_currency_code;
                $return[$currencyId] = [
                    'currency' => [
                        'id'     => $currencyId,
                        'code'   => $currencyCode,
                        'symbol' => $currencySymbol,
                        'dp'     => $decimalPlaces,
                    ],
                    'sum'      => '0',
                    'count'    => 0,
                ];
            }
            // save amount:
            $return[$currencyId]['sum'] = bcadd($return[$currencyId]['sum'], $transaction->transaction_amount);
            $return[$currencyId]['count']++;
        }
        asort($return);

        return $return;
    }

}
