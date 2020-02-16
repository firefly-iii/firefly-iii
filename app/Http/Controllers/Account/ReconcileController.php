<?php
/**
 * ReconcileController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** @noinspection CallableParameterUseCaseInTypeContextInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationStoreRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use FireflyIII\User;
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
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }


        if (AccountType::ASSET !== $account->accountType->type) {
            // @codeCoverageIgnoreStart
            session()->flash('error', (string)trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type))]));
            // @codeCoverageIgnoreEnd
        }
        $currency = $this->accountRepos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();

        // no start or end:
        $range = app('preferences')->get('viewRange', '1M')->data;

        // get start and end
        // @codeCoverageIgnoreStart
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
        // @codeCoverageIgnoreEnd
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
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
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        Log::debug('In ReconcileController::submit()');
        $data = $request->getAll();

        /** @var string $journalId */
        foreach ($data['journals'] as $journalId) {
            $this->repository->reconcileById((int)$journalId);
        }
        Log::debug('Reconciled all transactions.');

        // switch dates if necessary
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // create reconciliation transaction (if necessary):
        $result = '';
        if ('create' === $data['reconcile']) {
            $result = $this->createReconciliation($account, $start, $end, $data['difference']);
        }
        Log::debug('End of routine.');
        app('preferences')->mark();
        if ('' === $result) {
            session()->flash('success', (string)trans('firefly.reconciliation_stored'));
        }
        if ('' !== $result) {
            session()->flash('error', (string)trans('firefly.reconciliation_error', ['error' => $result]));
        }

        return redirect(route('accounts.show', [$account->id]));
    }

    /**
     * Creates a reconciliation group.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     * @param string  $difference
     *
     * @return string
     * @throws \FireflyIII\Exceptions\DuplicateTransactionException
     */
    private function createReconciliation(Account $account, Carbon $start, Carbon $end, string $difference)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $reconciliation = $this->accountRepos->getReconciliation($account);
        $currency       = $this->accountRepos->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();
        $source         = $reconciliation;
        $destination    = $account;
        if (1 === bccomp($difference, '0')) {
            $source      = $account;
            $destination = $reconciliation;
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // title:
        $description = trans('firefly.reconciliation_transaction_title',
                             ['from' => $start->formatLocalized($this->monthAndDayFormat), 'to'   => $end->formatLocalized($this->monthAndDayFormat)]);
        $submission = [
            'user'         => auth()->user()->id,
            'group_title'  => null,
            'transactions' => [
                [
                    'user'                => auth()->user()->id,
                    'type'                => strtolower(TransactionType::RECONCILIATION),
                    'date'                => $end,
                    'order'               => 0,
                    'currency_id'         => $currency->id,
                    'foreign_currency_id' => null,
                    'amount'              => $difference,
                    'foreign_amount'      => null,
                    'description'         => $description,
                    'source_id'           => $source->id,
                    'destination_id'      => $destination->id,
                    'reconciled'          => true,
                ],
            ],
        ];
        /** @var TransactionGroupFactory $factory */
        $factory = app(TransactionGroupFactory::class);
        /** @var User $user */
        $user = auth()->user();
        $factory->setUser($user);
        try {
            $factory->create($submission);
        } catch (FireflyException $e) {
            return $e->getMessage();
        }

        return '';
    }
}
