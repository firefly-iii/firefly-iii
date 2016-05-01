<?php
/**
 * TransactionController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Transaction;

use Auth;
use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Preferences;
use Response;
use Session;
use URL;
use View;

/**
 * Class TransactionController
 *
 * @package FireflyIII\Http\Controllers
 */
class TransactionController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.transactions'));
        View::share('mainTitleIcon', 'fa-repeat');
    }


    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Illuminate\View\View
     */
    public function index(JournalRepositoryInterface $repository, string $what)
    {
        $pageSize     = Preferences::get('transactionPageSize', 50)->data;
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $page         = intval(Input::get('page'));
        $journals     = $repository->getJournalsOfTypes($types, $page, $pageSize);

        $journals->setPath('transactions/' . $what);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function massDelete(Collection $journals)
    {
        $subTitle = trans('firefly.mass_delete_journals');

        // put previous url in session
        Session::put('transactions.mass-delete.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-delete');

        return view('transactions.mass-delete', compact('journals', 'subTitle'));

    }

    /**
     * @param MassDeleteJournalRequest   $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function massDestroy(MassDeleteJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $ids = $request->get('confirm_mass_delete');
        $set = new Collection;
        if (is_array($ids)) {
            /** @var int $journalId */
            foreach ($ids as $journalId) {
                /** @var TransactionJournal $journal */
                $journal = $repository->find($journalId);
                if (!is_null($journal->id) && $journalId == $journal->id) {
                    $set->push($journal);
                }
            }
        }
        unset($journal);
        $count = 0;

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $repository->delete($journal);
            $count++;
        }

        Preferences::mark();
        Session::flash('success', trans('firefly.mass_deleted_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect(session('transactions.mass-delete.url'));

    }

    /**
     * @param Collection $journals
     */
    public function massEdit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_edit_journals');
        /** @var ARI $accountRepository */
        $accountRepository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $accountList       = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));

        // put previous url in session
        Session::put('transactions.mass-edit.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-edit');

        return view('transactions.mass-edit', compact('journals', 'subTitle', 'accountList'));
    }

    /**
     *
     */
    public function massUpdate(MassEditJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = Input::get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->find(intval($journalId));
                if ($journal) {
                    // do update.

                    // get optional fields:
                    $what            = strtolower(TransactionJournal::transactionTypeStr($journal));
                    $sourceAccountId = $request->get('source_account_id')[$journal->id] ??  0;
                    $destAccountId   = $request->get('destination_account_id')[$journal->id] ??  0;
                    $expenseAccount  = $request->get('expense_account')[$journal->id] ?? '';
                    $revenueAccount  = $request->get('revenue_account')[$journal->id] ?? '';
                    $budgetId        = $journal->budgets->first() ? $journal->budgets->first()->id : 0;
                    $category        = $journal->categories->first() ? $journal->categories->first()->name : '';
                    $tags            = $journal->tags->pluck('tag')->toArray();

                    // for a deposit, the 'account_id' is the account the money is deposited on.
                    // needs a better way of handling.
                    // more uniform source/destination field names
                    $accountId = $sourceAccountId;
                    if ($what == 'deposit') {
                        $accountId = $destAccountId;
                    }

                    // build data array
                    $data = [
                        'id'                        => $journal->id,
                        'what'                      => $what,
                        'description'               => $request->get('description')[$journal->id],
                        'account_id'                => intval($accountId),
                        'account_from_id'           => intval($sourceAccountId),
                        'account_to_id'             => intval($destAccountId),
                        'expense_account'           => $expenseAccount,
                        'revenue_account'           => $revenueAccount,
                        'amount'                    => round($request->get('amount')[$journal->id], 4),
                        'user'                      => Auth::user()->id,
                        'amount_currency_id_amount' => intval($request->get('amount_currency_id_amount_' . $journal->id)),
                        'date'                      => new Carbon($request->get('date')[$journal->id]),
                        'interest_date'             => $journal->interest_date,
                        'book_date'                 => $journal->book_date,
                        'process_date'              => $journal->process_date,
                        'budget_id'                 => $budgetId,
                        'category'                  => $category,
                        'tags'                      => $tags,

                    ];
                    // call repository update function.
                    $repository->update($journal, $data);

                    $count++;
                }
            }
        }
        Preferences::mark();
        Session::flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect(session('transactions.mass-edit.url'));

    }

    /**
     * @param JournalRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reorder(JournalRepositoryInterface $repository)
    {
        $ids  = Input::get('items');
        $date = new Carbon(Input::get('date'));
        if (count($ids) > 0) {
            $order = 0;
            foreach ($ids as $id) {

                $journal = $repository->getWithDate($id, $date);
                if ($journal) {
                    $journal->order = $order;
                    $order++;
                    $journal->save();
                }
            }
        }
        Preferences::mark();

        return Response::json([true]);

    }


}
