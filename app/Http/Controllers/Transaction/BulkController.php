<?php
/**
 * BulkController.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;


use ExpandedForm;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassEditBulkJournalRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use View;

/**
 * Class BulkController
 */
class BulkController extends Controller
{


    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function edit(Collection $journals)
    {

        $subTitle = trans('firefly.mass_bulk_journals');

        // skip transactions that have multiple destinations, multiple sources or are an opening balance.
        $filtered = new Collection;
        $messages = [];
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sources      = $journal->sourceAccountList();
            $destinations = $journal->destinationAccountList();
            if ($sources->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_source', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            if ($destinations->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_dest', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }
            if (TransactionType::OPENING_BALANCE === $journal->transactionType->type) {
                $messages[] = trans('firefly.cannot_edit_opening_balance');
                continue;
            }

            // cannot edit reconciled transactions / journals:
            if ($journal->transactions->first()->reconciled) {
                $messages[] = trans('firefly.cannot_edit_reconciled', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            $filtered->push($journal);
        }

        if (count($messages) > 0) {
            Session::flash('info', $messages);
        }

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-edit-bulk.uri');

        // get list of budgets:
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgetList = ExpandedForm::makeSelectListWithEmpty($repository->getActiveBudgets());
        // collect some useful meta data for the mass edit:
        $filtered->each(
            function (TransactionJournal $journal) {
                $journal->transaction_count = $journal->transactions()->count();
            }
        );

        if (0 === $filtered->count()) {
            Session::flash('error', trans('firefly.no_edit_multiple_left'));
        }

        $journals = $filtered;

        return view('transactions.bulk.edit', compact('journals', 'subTitle','budgetList'));
    }


    /**
     * @param MassEditBulkJournalRequest $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function updateBulk(MassEditBulkJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = $request->get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            $count = $repository->updateBulk($journalIds, $request->get('category'), $request->get('tags'));
        }
        Preferences::mark();
        Session::flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-edit-bulk.uri'));
    }

}