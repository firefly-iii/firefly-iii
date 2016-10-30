<?php
/**
 * MassController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use URL;
use View;

/**
 * Class MassController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 */
class MassController extends Controller
{
    /**
     *
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
                $journal = $repository->find(intval($journalId));
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
     *
     * @return View
     */
    public function massEdit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $repository */
        $repository  = app(AccountRepositoryInterface::class);
        $accountList = ExpandedForm::makeSelectList($repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]));

        // skip transactions that have multiple destinations
        // or multiple sources:
        $filtered = new Collection;
        $messages = [];
        /**
         * @var int                $index
         * @var TransactionJournal $journal
         */
        foreach ($journals as $index => $journal) {
            $sources      = TransactionJournal::sourceAccountList($journal);
            $destinations = TransactionJournal::destinationAccountList($journal);
            if ($sources->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_source', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            if ($destinations->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_dest', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }
            $filtered->push($journal);
        }

        if (count($messages)) {
            Session::flash('info', $messages);
        }

        // put previous url in session
        Session::put('transactions.mass-edit.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-edit');

        // set some values to be used in the edit routine:
        $filtered->each(
            function (TransactionJournal $journal) {
                $journal->amount            = TransactionJournal::amountPositive($journal);
                $sources                    = TransactionJournal::sourceAccountList($journal);
                $destinations               = TransactionJournal::destinationAccountList($journal);
                $journal->transaction_count = $journal->transactions()->count();
                if (!is_null($sources->first())) {
                    $journal->source_account_id   = $sources->first()->id;
                    $journal->source_account_name = $sources->first()->name;
                }
                if (!is_null($destinations->first())) {
                    $journal->destination_account_id   = $destinations->first()->id;
                    $journal->destination_account_name = $destinations->first()->name;
                }
            }
        );

        if ($filtered->count() === 0) {
            Session::flash('error', trans('firefly.no_edit_multiple_left'));
        }

        $journals = $filtered;

        return view('transactions.mass-edit', compact('journals', 'subTitle', 'accountList'));
    }

    /**
     * @param MassEditJournalRequest     $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function massUpdate(MassEditJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = $request->get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->find(intval($journalId));
                if ($journal) {
                    // get optional fields:
                    $what = strtolower(TransactionJournal::transactionTypeStr($journal));

                    $sourceAccountId   = $request->get('source_account_id')[$journal->id] ??  0;
                    $sourceAccountName = $request->get('source_account_name')[$journal->id] ?? '';
                    $destAccountId     = $request->get('destination_account_id')[$journal->id] ??  0;
                    $destAccountName   = $request->get('destination_account_name')[$journal->id] ?? '';

                    $budgetId = $journal->budgets->first() ? $journal->budgets->first()->id : 0;
                    $category = $request->get('category')[$journal->id];
                    $tags     = $journal->tags->pluck('tag')->toArray();

                    // build data array
                    $data = [
                        'id'                        => $journal->id,
                        'what'                      => $what,
                        'description'               => $request->get('description')[$journal->id],
                        'source_account_id'         => intval($sourceAccountId),
                        'source_account_name'       => $sourceAccountName,
                        'destination_account_id'    => intval($destAccountId),
                        'destination_account_name'  => $destAccountName,
                        'amount'                    => round($request->get('amount')[$journal->id], 4),
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
}
