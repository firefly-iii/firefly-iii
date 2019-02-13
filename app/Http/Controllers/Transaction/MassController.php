<?php
/**
 * MassController.php
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

use Carbon\Carbon;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\TransactionViewFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\View\View as IlluminateView;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class MassController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassController extends Controller
{
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * MassController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');
                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Mass delete transactions.
     *
     * @param Collection $journals
     *
     * @return IlluminateView
     */
    public function delete(Collection $journals): IlluminateView
    {
        $subTitle = (string)trans('firefly.mass_delete_journals');

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-delete.uri');

        return view('transactions.mass.delete', compact('journals', 'subTitle'));
    }

    /**
     * Do the mass delete.
     *
     * @param MassDeleteJournalRequest $request
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function destroy(MassDeleteJournalRequest $request)
    {
        $ids   = $request->get('confirm_mass_delete');
        $count = 0;
        if (\is_array($ids)) {
            /** @var string $journalId */
            foreach ($ids as $journalId) {
                /** @var TransactionJournal $journal */
                $journal = $this->repository->findNull((int)$journalId);
                if (null !== $journal && (int)$journalId === $journal->id) {
                    $this->repository->destroy($journal);
                    ++$count;
                }
            }
        }


        app('preferences')->mark();
        session()->flash('success', (string)trans('firefly.mass_deleted_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-delete.uri'));
    }

    /**
     * Mass edit of journals.
     *
     * @param Collection $journals
     *
     * @return IlluminateView
     */
    public function edit(Collection $journals): IlluminateView
    {
        /** @var User $user */
        $user     = auth()->user();
        $subTitle = (string)trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgets          = $budgetRepository->getBudgets();

        $this->rememberPreviousUri('transactions.mass-edit.uri');

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters(new ParameterBag);

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($user);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setJournals($journals);
        $collector->addFilter(TransactionViewFilter::class);
        $collector->addFilter(TransferFilter::class);


        $collection   = $collector->getTransactions();
        $transactions = $collection->map(
            function (Transaction $transaction) use ($transformer) {
                $transformed = $transformer->transform($transaction);
                // make sure amount is positive:
                $transformed['amount']         = app('steam')->positive((string)$transformed['amount']);
                $transformed['foreign_amount'] = app('steam')->positive((string)$transformed['foreign_amount']);

                return $transformed;
            }
        );

        return view('transactions.mass.edit', compact('transactions', 'subTitle', 'accounts', 'budgets'));
    }

    /**
     * Mass update of journals.
     *
     * @param MassEditJournalRequest     $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function update(MassEditJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = $request->get('journals');
        $count      = 0;
        if (\is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->findNull((int)$journalId);
                if (null !== $journal) {
                    // get optional fields:
                    $what              = strtolower($this->repository->getTransactionType($journal));
                    $sourceAccountId   = $request->get('source_id')[$journal->id] ?? null;
                    $currencyId        = $request->get('transaction_currency_id')[$journal->id] ?? 1;
                    $sourceAccountName = $request->get('source_name')[$journal->id] ?? null;
                    $destAccountId     = $request->get('destination_id')[$journal->id] ?? null;
                    $destAccountName   = $request->get('destination_name')[$journal->id] ?? null;
                    $budgetId          = (int)($request->get('budget_id')[$journal->id] ?? 0.0);
                    $category          = $request->get('category')[$journal->id];
                    $tags              = $journal->tags->pluck('tag')->toArray();
                    $amount            = round($request->get('amount')[$journal->id], 12);
                    $foreignAmount     = isset($request->get('foreign_amount')[$journal->id]) ? round($request->get('foreign_amount')[$journal->id], 12) : null;
                    $foreignCurrencyId = isset($request->get('foreign_currency_id')[$journal->id]) ?
                        (int)$request->get('foreign_currency_id')[$journal->id] : null;
                    // build data array
                    $data = [
                        'id'            => $journal->id,
                        'what'          => $what,
                        'description'   => $request->get('description')[$journal->id],
                        'date'          => new Carbon($request->get('date')[$journal->id]),
                        'bill_id'       => null,
                        'bill_name'     => null,
                        'notes'         => $repository->getNoteText($journal),
                        'transactions'  => [[

                                                'category_id'           => null,
                                                'category_name'         => $category,
                                                'budget_id'             => $budgetId,
                                                'budget_name'           => null,
                                                'source_id'             => (int)$sourceAccountId,
                                                'source_name'           => $sourceAccountName,
                                                'destination_id'        => (int)$destAccountId,
                                                'destination_name'      => $destAccountName,
                                                'amount'                => $amount,
                                                'identifier'            => 0,
                                                'reconciled'            => false,
                                                'currency_id'           => (int)$currencyId,
                                                'currency_code'         => null,
                                                'description'           => null,
                                                'foreign_amount'        => $foreignAmount,
                                                'foreign_currency_id'   => $foreignCurrencyId,
                                                'foreign_currency_code' => null,
                                            ]],
                        'currency_id'   => $foreignCurrencyId,
                        'tags'          => $tags,
                        'interest_date' => $journal->interest_date,
                        'book_date'     => $journal->book_date,
                        'process_date'  => $journal->process_date,

                    ];
                    // call repository update function.
                    $repository->update($journal, $data);

                    // trigger rules
                    event(new UpdatedTransactionJournal($journal));

                    ++$count;
                }
            }
        }
        app('preferences')->mark();
        session()->flash('success', (string)trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-edit.uri'));
    }

}
