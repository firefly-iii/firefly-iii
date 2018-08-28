<?php
/**
 * ReconcileController.php
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
/** @noinspection CallableParameterUseCaseInTypeContextInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationStoreRequest;
use FireflyIII\Http\Requests\ReconciliationUpdateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Log;

/**
 * Class ReconcileController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileController extends Controller
{
    use UserNavigation;
    /** @var AccountRepositoryInterface The account repository */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * ReconcileController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));
                $this->repository    = app(JournalRepositoryInterface::class);
                $this->accountRepos  = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a reconciliation.
     *
     * @param TransactionJournal $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit(TransactionJournal $journal)
    {
        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.edit', [$journal->id]));
        }
        // view related code
        $subTitle = (string)trans('breadcrumbs.edit_journal', ['description' => $journal->description]);

        // journal related code
        $pTransaction = $this->repository->getFirstPosTransaction($journal);
        $preFilled    = [
            'date'     => $this->repository->getJournalDate($journal, null),
            'category' => $this->repository->getJournalCategoryName($journal),
            'tags'     => implode(',', $journal->tags->pluck('tag')->toArray()),
            'amount'   => $pTransaction->amount,
        ];

        session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('reconcile.edit.fromUpdate')) {
            $this->rememberPreviousUri('reconcile.edit.uri');
        }
        session()->forget('reconcile.edit.fromUpdate');

        return view(
            'accounts.reconcile.edit',
            compact('journal', 'subTitle')
        )->with('data', $preFilled);
    }

    /**
     * Reconciliation overview.
     *
     * @param Account     $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws FireflyException
     */
    public function reconcile(Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }
        if (AccountType::ASSET !== $account->accountType->type) {
            session()->flash('error', (string)trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config('firefly.shortNamesByFullName.' . $account->accountType->type)]));
        }
        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $currency   = $this->currencyRepos->findNull($currencyId);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }

        // no start or end:
        $range = app('preferences')->get('viewRange', '1M')->data;

        // get start and end
        if (null === $start && null === $end) {
            /** @var Carbon $start */
            $start = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            /** @var Carbon $end */
            $end = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
        }
        if (null === $end) {
            /** @var Carbon $end */
            $end = app('navigation')->endOfPeriod($start, $range);
        }

        $startDate = clone $start;
        $startDate->subDays(1);
        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = (string)trans('firefly.reconcile_account', ['account' => $account->name]);

        // various links
        $transactionsUri = route('accounts.reconcile.transactions', [$account->id, '%start%', '%end%']);
        $overviewUri     = route('accounts.reconcile.overview', [$account->id, '%start%', '%end%']);
        $indexUri        = route('accounts.reconcile', [$account->id, '%start%', '%end%']);

        return view(
            'accounts.reconcile.index', compact(
                                          'account', 'currency', 'subTitleIcon', 'start', 'end', 'subTitle', 'startBalance', 'endBalance', 'transactionsUri',
                                          'overviewUri', 'indexUri'
                                      )
        );
    }

    /**
     * Show a single reconciliation.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws FireflyException
     */
    public function show(TransactionJournal $journal)
    {

        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        $subTitle = trans('firefly.reconciliation') . ' "' . $journal->description . '"';

        // get main transaction:
        $transaction = $this->repository->getAssetTransaction($journal);
        if (null === $transaction) {
            throw new FireflyException('The transaction data is incomplete. This is probably a bug. Apologies.');
        }
        $account = $transaction->account;

        return view('accounts.reconcile.show', compact('journal', 'subTitle', 'transaction', 'account'));
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Submit a new reconciliation.
     *
     * @param ReconciliationStoreRequest $request
     * @param Account                    $account
     * @param Carbon                     $start
     * @param Carbon                     $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submit(ReconciliationStoreRequest $request, Account $account, Carbon $start, Carbon $end)
    {
        Log::debug('In ReconcileController::submit()');
        $data = $request->getAll();

        /** @var Transaction $transaction */
        foreach ($data['transactions'] as $transactionId) {
            $this->repository->reconcileById((int)$transactionId);
        }
        Log::debug('Reconciled all transactions.');

        // create reconciliation transaction (if necessary):
        if ('create' === $data['reconcile']) {
            // get "opposing" account.
            $reconciliation = $this->accountRepos->getReconciliation($account);
            $difference     = $data['difference'];
            $source         = $reconciliation;
            $destination    = $account;
            if (1 === bccomp($difference, '0')) {
                // amount is positive. Add it to reconciliation?
                $source      = $account;
                $destination = $reconciliation;
            }

            // data for journal
            $description = trans(
                'firefly.reconcilliation_transaction_title',
                ['from' => $start->formatLocalized($this->monthAndDayFormat), 'to' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $journalData = [
                'type'            => 'Reconciliation',
                'description'     => $description,
                'user'            => auth()->user()->id,
                'date'            => $data['end'],
                'bill_id'         => null,
                'bill_name'       => null,
                'piggy_bank_id'   => null,
                'piggy_bank_name' => null,
                'tags'            => null,
                'interest_date'   => null,
                'transactions'    => [[
                                          'currency_id'           => (int)$this->accountRepos->getMetaValue($account, 'currency_id'),
                                          'currency_code'         => null,
                                          'description'           => null,
                                          'amount'                => app('steam')->positive($difference),
                                          'source_id'             => $source->id,
                                          'source_name'           => null,
                                          'destination_id'        => $destination->id,
                                          'destination_name'      => null,
                                          'reconciled'            => true,
                                          'identifier'            => 0,
                                          'foreign_currency_id'   => null,
                                          'foreign_currency_code' => null,
                                          'foreign_amount'        => null,
                                          'budget_id'             => null,
                                          'budget_name'           => null,
                                          'category_id'           => null,
                                          'category_name'         => null,
                                      ],
                ],
                'notes'           => implode(', ', $data['transactions']),
            ];

            $this->repository->store($journalData);
        }
        Log::debug('End of routine.');
        app('preferences')->mark();
        session()->flash('success', (string)trans('firefly.reconciliation_stored'));

        return redirect(route('accounts.show', [$account->id]));
    }


    /**
     * Update a reconciliation.
     *
     * @param ReconciliationUpdateRequest $request
     * @param TransactionJournal          $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function update(ReconciliationUpdateRequest $request, TransactionJournal $journal)
    {
        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        if (0 === bccomp('0', $request->get('amount'))) {
            session()->flash('error', (string)trans('firefly.amount_cannot_be_zero'));

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput();
        }
        // update journal using account repository. Keep it consistent.
        $submitted = $request->getJournalData();

        // amount pos neg influences the accounts:
        $source      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destination = $this->repository->getJournalDestinationAccounts($journal)->first();
        if (1 === bccomp($submitted['amount'], '0')) {
            // amount is positive, switch accounts:
            [$source, $destination] = [$destination, $source];

        }
        // expand data with journal data:
        $data = [
            'type'            => $journal->transactionType->type,
            'description'     => $journal->description,
            'user'            => $journal->user_id,
            'date'            => $journal->date,
            'bill_id'         => null,
            'bill_name'       => null,
            'piggy_bank_id'   => null,
            'piggy_bank_name' => null,
            'tags'            => $submitted['tags'],
            'interest_date'   => null,
            'book_date'       => null,
            'transactions'    => [[
                                      'currency_id'           => (int)$journal->transaction_currency_id,
                                      'currency_code'         => null,
                                      'description'           => null,
                                      'amount'                => app('steam')->positive($submitted['amount']),
                                      'source_id'             => $source->id,
                                      'source_name'           => null,
                                      'destination_id'        => $destination->id,
                                      'destination_name'      => null,
                                      'reconciled'            => true,
                                      'identifier'            => 0,
                                      'foreign_currency_id'   => null,
                                      'foreign_currency_code' => null,
                                      'foreign_amount'        => null,
                                      'budget_id'             => null,
                                      'budget_name'           => null,
                                      'category_id'           => null,
                                      'category_name'         => $submitted['category'],
                                  ],
            ],
            'notes'           => $this->repository->getNoteText($journal),
        ];

        $this->repository->update($journal, $data);


        // @codeCoverageIgnoreStart
        if (1 === (int)$request->get('return_to_edit')) {
            session()->put('reconcile.edit.fromUpdate', true);

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('reconcile.edit.uri'));
    }
}
