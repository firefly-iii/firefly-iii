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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalUpdateInterface;
use Illuminate\Support\Collection;
use Preferences;
use Session;
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
    public function delete(Collection $journals)
    {
        $subTitle = trans('firefly.mass_delete_journals');

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-delete.uri');
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
    public function destroy(MassDeleteJournalRequest $request, JournalRepositoryInterface $repository)
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
        return redirect($this->getPreviousUri('transactions.mass-delete.uri'));

    }


    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function edit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        // get budgets
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgets          = $budgetRepository->getBudgets();

        // skip transactions that have multiple destinations, multiple sources or are an opening balance.
        $filtered = new Collection;
        $messages = [];
        /**
         * @var int                $index
         * @var TransactionJournal $journal
         */
        foreach ($journals as $index => $journal) {
            $sources      = $journal->sourceAccountList($journal);
            $destinations = $journal->destinationAccountList($journal);
            if ($sources->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_source', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            if ($destinations->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_dest', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }
            if ($journal->transactionType->type === TransactionType::OPENING_BALANCE) {
                $messages[] = trans('firefly.cannot_edit_opening_balance');
                continue;
            }
            $filtered->push($journal);
        }

        if (count($messages) > 0) {
            Session::flash('info', $messages);
        }

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-edit.uri');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-edit');

        // collect some useful meta data for the mass edit:
        $filtered->each(
            function (TransactionJournal $journal) {
                $transaction                    = $journal->positiveTransaction();
                $currency                       = $transaction->transactionCurrency;
                $journal->amount                = floatval($transaction->amount);
                $sources                        = $journal->sourceAccountList();
                $destinations                   = $journal->destinationAccountList();
                $journal->transaction_count     = $journal->transactions()->count();
                $journal->currency_symbol       = $currency->symbol;
                $journal->transaction_type_type = $journal->transactionType->type;

                $journal->foreign_amount   = floatval($transaction->foreign_amount);
                $journal->foreign_currency = $transaction->foreignCurrency;

                if (!is_null($sources->first())) {
                    $journal->source_account_id   = $sources->first()->id;
                    $journal->source_account_name = $sources->first()->editname;
                }
                if (!is_null($destinations->first())) {
                    $journal->destination_account_id   = $destinations->first()->id;
                    $journal->destination_account_name = $destinations->first()->editname;
                }
            }
        );

        if ($filtered->count() === 0) {
            Session::flash('error', trans('firefly.no_edit_multiple_left'));
        }

        $journals = $filtered;

        return view('transactions.mass.edit', compact('journals', 'subTitle', 'accounts', 'budgets'));
    }

    /**
     * @param MassEditJournalRequest     $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function update(MassEditJournalRequest $request, JournalRepositoryInterface $repository, JournalUpdateInterface $updater)
    {
        $journalIds = $request->get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->find(intval($journalId));
                if ($journal) {
                    // get optional fields:
                    $what              = strtolower($journal->transactionTypeStr());
                    $sourceAccountId   = $request->get('source_account_id')[$journal->id] ??  0;
                    $sourceAccountName = $request->get('source_account_name')[$journal->id] ?? '';
                    $destAccountId     = $request->get('destination_account_id')[$journal->id] ??  0;
                    $destAccountName   = $request->get('destination_account_name')[$journal->id] ?? '';
                    $budgetId          = $request->get('budget_id')[$journal->id] ??  0;
                    $category          = $request->get('category')[$journal->id];
                    $tags              = $journal->tags->pluck('tag')->toArray();
                    $amount            = round($request->get('amount')[$journal->id], 12);
                    $foreignAmount     = isset($request->get('foreign_amount')[$journal->id]) ? round($request->get('foreign_amount')[$journal->id], 12) : null;
                    $foreignCurrencyId = isset($request->get('foreign_currency_id')[$journal->id]) ?
                        intval($request->get('foreign_currency_id')[$journal->id]) : null;

                    // build data array
                    $data = [
                        'id'                       => $journal->id,
                        'what'                     => $what,
                        'description'              => $request->get('description')[$journal->id],
                        'source_account_id'        => intval($sourceAccountId),
                        'source_account_name'      => $sourceAccountName,
                        'destination_account_id'   => intval($destAccountId),
                        'destination_account_name' => $destAccountName,
                        'amount'                   => $foreignAmount,
                        'native_amount'            => $amount,
                        'source_amount'            => $amount,
                        'date'                     => new Carbon($request->get('date')[$journal->id]),
                        'interest_date'            => $journal->interest_date,
                        'book_date'                => $journal->book_date,
                        'process_date'             => $journal->process_date,
                        'budget_id'                => intval($budgetId),
                        'currency_id'              => $foreignCurrencyId,
                        'foreign_amount'           => $foreignAmount,
                        'destination_amount'       => $foreignAmount,
                        //'foreign_currency_id'      => $foreignCurrencyId,
                        'category'                 => $category,
                        'tags'                     => $tags,
                    ];
                    // call repository update function.
                    $updater->update($journal, $data);

                    $count++;
                }
            }

        }
        Preferences::mark();
        Session::flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-edit.uri'));

    }
}
