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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationStoreRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class ReconcileController.
 */
class ReconcileController extends Controller
{
    private AccountRepositoryInterface $accountRepos;
    private JournalRepositoryInterface $repository;

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
                app('view')->share('title', (string) trans('firefly.accounts'));
                $this->repository   = app(JournalRepositoryInterface::class);
                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Reconciliation overview.
     *
     * @return Factory|Redirector|RedirectResponse|View
     *
     * @throws FireflyException
     *                                              */
    public function reconcile(Account $account, ?Carbon $start = null, ?Carbon $end = null)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }
        if (AccountTypeEnum::ASSET->value !== $account->accountType->type) {
            session()->flash('error', (string) trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config(sprintf('firefly.shortNamesByFullName.%s', $account->accountType->type))]));
        }
        $currency        = $this->accountRepos->getAccountCurrency($account) ?? $this->defaultCurrency;

        // no start or end:
        $range           = app('navigation')->getViewRange(false);

        // get start and end

        if (null === $start && null === $end) {
            /** @var Carbon $start */
            $start = clone session('start', app('navigation')->startOfPeriod(new Carbon(), $range));

            /** @var Carbon $end */
            $end   = clone session('end', app('navigation')->endOfPeriod(new Carbon(), $range));
        }
        if (null === $end) {
            /** @var Carbon $end */
            $end = app('navigation')->endOfPeriod($start, $range);
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        // move dates to end of day and start of day:
        $start->startOfDay();
        $end->endOfDay();

        $startDate       = clone $start;
        $startDate->subDay()->endOfDay(); // this is correct, subday endofday ends at 23:59:59
        // both are validated and are correct.
        Log::debug(sprintf('reconcile: Call finalAccountBalance with date/time "%s"', $startDate->toIso8601String()));
        Log::debug(sprintf('reconcile2: Call finalAccountBalance with date/time "%s"', $end->toIso8601String()));
        $startBalance    = Steam::bcround(Steam::finalAccountBalance($account, $startDate)['balance'], $currency->decimal_places);
        $endBalance      = Steam::bcround(Steam::finalAccountBalance($account, $end)['balance'], $currency->decimal_places);
        $subTitleIcon    = config(sprintf('firefly.subIconsByIdentifier.%s', $account->accountType->type));
        $subTitle        = (string) trans('firefly.reconcile_account', ['account' => $account->name]);

        // various links
        $transactionsUrl = route('accounts.reconcile.transactions', [$account->id, '%start%', '%end%']);
        $overviewUrl     = route('accounts.reconcile.overview', [$account->id, '%start%', '%end%']);
        $indexUrl        = route('accounts.reconcile', [$account->id, '%start%', '%end%']);
        $objectType      = 'asset';

        return view(
            'accounts.reconcile.index',
            compact(
                'account',
                'currency',
                'objectType',
                'subTitleIcon',
                'start',
                'end',
                'subTitle',
                'startBalance',
                'endBalance',
                'transactionsUrl',
                'overviewUrl',
                'indexUrl'
            )
        );
    }

    /**
     * Submit a new reconciliation.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws DuplicateTransactionException
     */
    public function submit(ReconciliationStoreRequest $request, Account $account, Carbon $start, Carbon $end)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        app('log')->debug('In ReconcileController::submit()');
        $data   = $request->getAll();

        /** @var string $journalId */
        foreach ($data['journals'] as $journalId) {
            $this->repository->reconcileById((int) $journalId);
        }
        app('log')->debug('Reconciled all transactions.');

        // switch dates if necessary
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // create reconciliation transaction (if necessary):
        $result = '';
        if ('create' === $data['reconcile']) {
            $result = $this->createReconciliation($account, $start, $end, $data['difference']);
        }
        app('log')->debug('End of routine.');
        app('preferences')->mark();
        if ('' === $result) {
            session()->flash('success', (string) trans('firefly.reconciliation_stored'));
        }
        if ('' !== $result) {
            session()->flash('error', (string) trans('firefly.reconciliation_error', ['error' => $result]));
        }

        return redirect(route('accounts.show', [$account->id]));
    }

    /**
     * Creates a reconciliation group.
     *
     * @throws DuplicateTransactionException
     */
    private function createReconciliation(Account $account, Carbon $start, Carbon $end, string $difference): string
    {
        if (!$this->isEditableAccount($account)) {
            return 'not-editable';
        }

        $reconciliation = $this->accountRepos->getReconciliation($account);
        $currency       = $this->accountRepos->getAccountCurrency($account) ?? $this->defaultCurrency;
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
        $description    = trans(
            'firefly.reconciliation_transaction_title',
            [
                'from' => $start->isoFormat($this->monthAndDayFormat),
                'to'   => $end->isoFormat($this->monthAndDayFormat),
            ]
        );
        $submission     = [
            'user'         => auth()->user(),
            'user_group'   => auth()->user()->userGroup,
            'group_title'  => null,
            'transactions' => [
                [
                    'user'                => auth()->user(),
                    'user_group'          => auth()->user()->userGroup,
                    'type'                => strtolower(TransactionTypeEnum::RECONCILIATION->value),
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
        $factory        = app(TransactionGroupFactory::class);

        /** @var User $user */
        $user           = auth()->user();
        $factory->setUser($user);

        try {
            $factory->create($submission);
        } catch (FireflyException $e) {
            return $e->getMessage();
        }

        return '';
    }
}
