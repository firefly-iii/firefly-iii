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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
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
     * @param Request $request
     * @param string  $what
     *
     * @return View
     */
    public function index(Request $request, JournalRepositoryInterface $repository, string $what)
    {
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $range        = Preferences::get('viewRange', '1M')->data;
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));
        // to make sure we only grab a subset, based on the current date (in session):
        $start = session('start', Navigation::startOfPeriod(new Carbon, $range));
        $end   = session('end', Navigation::endOfPeriod(new Carbon, $range));


        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setTypes($types)->setLimit($pageSize)->setPage($page)->setAllAssetAccounts();
        $collector->setRange($start, $end);

        // do not filter transfers if $what = transfer.
        if (!in_array($what, ['transfer', 'transfers'])) {
            Log::debug('Also get opposing account info.');
            $collector->withOpposingAccount();
        }

        $journals = $collector->getPaginatedJournals();
        $journals->setPath('transactions/' . $what);

        unset($start, $end);

        // then also show a list of periods where the user can click on, based on the
        // user's range and the oldest journal the user has:
        $first      = $repository->first();
        $blockStart = is_null($first->id) ? new Carbon : $first->date;
        $blockStart = Navigation::startOfPeriod($blockStart, $range);
        $blockEnd   = Navigation::endOfX(new Carbon, $range);
        $entries    = new Collection;

        while ($blockEnd >= $blockStart) {
            Log::debug(sprintf('Now at blockEnd: %s', $blockEnd->format('Y-m-d')));
            $blockEnd = Navigation::startOfPeriod($blockEnd, $range);
            $dateStr  = $blockEnd->format('Y-m-d');
            $dateName = Navigation::periodShow($blockEnd, $range);
            $entries->push([$dateStr, $dateName]);
            $blockEnd = Navigation::subtractPeriod($blockEnd, $range, 1);
        }

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals', 'entries'));

    }

    /**
     * @param Request $request
     * @param string  $what
     *
     * @return View
     */
    public function indexAll(Request $request, string $what)
    {
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = sprintf('%s (%s)', trans('firefly.title_' . $what), strtolower(trans('firefly.everything')));
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));

        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setTypes($types)->setLimit($pageSize)->setPage($page)->setAllAssetAccounts();

        // do not filter transfers if $what = transfer.
        if (!in_array($what, ['transfer', 'transfers'])) {
            Log::debug('Also get opposing account info.');
            $collector->withOpposingAccount();
        }

        $journals = $collector->getPaginatedJournals();
        $journals->setPath('transactions/' . $what . '/all');

        return view('transactions.index-all', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }

    /**
     * @param Request $request
     * @param string  $what
     *
     * @return View
     */
    public function indexDate(Request $request, string $what, string $date)
    {
        $carbon       = new Carbon($date);
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = Navigation::startOfPeriod($carbon, $range);
        $end          = Navigation::endOfPeriod($carbon, $range);
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what) . ' (' . Navigation::periodShow($carbon, $range) . ')';
        $page         = intval($request->get('page')) === 0 ? 1 : intval($request->get('page'));

        Log::debug(sprintf('Transaction index by date will show between %s and %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setTypes($types)->setLimit($pageSize)->setPage($page)->setAllAssetAccounts();
        $collector->setRange($start, $end);

        // do not filter transfers if $what = transfer.
        if (!in_array($what, ['transfer', 'transfers'])) {
            Log::debug('Also get opposing account info.');
            $collector->withOpposingAccount();
        }

        $journals = $collector->getPaginatedJournals();
        $journals->setPath('transactions/' . $what . '/' . $date);

        return view('transactions.index-date', compact('subTitle', 'what', 'subTitleIcon', 'journals', 'carbon'));

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
                if ($journal && $journal->date->format('Y-m-d') == $date->format('Y-m-d')) {
                    $journal->order = $order;
                    $order++;
                    $journal->save();
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
        $events       = $tasker->getPiggyBankEvents($journal);
        $transactions = $tasker->getTransactionsOverview($journal);
        $what         = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle     = trans('firefly.' . $what) . ' "' . e($journal->description) . '"';

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what', 'transactions'));


    }
}
