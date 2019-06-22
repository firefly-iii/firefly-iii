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
use Exception;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationStoreRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Log;

/**
 * Class ReconcileController.
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
     * @codeCoverageIgnore
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
     * Reconciliation overview.
     *
     * @param Account $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws Exception
     */
    public function reconcile(Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }
        if (AccountType::ASSET !== $account->accountType->type) {
            session()->flash('error', (string)trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type))]));
        }
        $currency = $this->accountRepos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();

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
        $startDate->subDay();
        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);
        $subTitleIcon = config(sprintf('firefly.subIconsByIdentifier.%s', $account->accountType->type));
        $subTitle     = (string)trans('firefly.reconcile_account', ['account' => $account->name]);

        // various links
        $transactionsUri = route('accounts.reconcile.transactions', [$account->id, '%start%', '%end%']);
        $overviewUri     = route('accounts.reconcile.overview', [$account->id, '%start%', '%end%']);
        $indexUri        = route('accounts.reconcile', [$account->id, '%start%', '%end%']);
        $objectType      = 'asset';

        return view('accounts.reconcile.index',
                    compact('account', 'currency', 'objectType',
                            'subTitleIcon', 'start', 'end', 'subTitle', 'startBalance', 'endBalance',
                            'transactionsUri', 'overviewUri', 'indexUri'));
    }

    /**
     * Submit a new reconciliation.
     *
     * @param ReconciliationStoreRequest $request
     * @param Account $account
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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
}
