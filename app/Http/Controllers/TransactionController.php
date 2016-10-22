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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use Illuminate\Http\Request;
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
        View::share('title', trans('firefly.transactions'));
        View::share('mainTitleIcon', 'fa-repeat');

    }

    /**
     * @param Request                $request
     * @param JournalTaskerInterface $tasker
     * @param string                 $what
     *
     * @return View
     */
    public function index(Request $request, JournalTaskerInterface $tasker, string $what)
    {
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $page         = intval($request->get('page'));
        $journals     = $tasker->getJournals($types, $page, $pageSize);

        $journals->setPath('transactions/' . $what);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

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
