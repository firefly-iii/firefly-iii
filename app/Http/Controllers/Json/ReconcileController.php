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

namespace FireflyIII\Http\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class ReconcileController
 */
class ReconcileController extends Controller
{
    private AccountRepositoryInterface $accountRepos;

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
                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Overview of reconciliation.
     *
     * @throws FireflyException
     */
    public function overview(Request $request, ?Account $account = null, ?Carbon $start = null, ?Carbon $end = null): JsonResponse
    {
        $startBalance    = $request->get('startBalance');
        $endBalance      = $request->get('endBalance');
        $accountCurrency = $this->accountRepos->getAccountCurrency($account) ?? $this->defaultCurrency;
        $amount          = '0';
        $clearedAmount   = '0';

        if (null === $start && null === $end) {
            throw new FireflyException('Invalid dates submitted.');
        }
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        $end->endOfDay();
        $start->startOfDay();

        $route           = route('accounts.reconcile.submit', [$account->id, $start->format('Ymd'), $end->format('Ymd')]);
        $selectedIds     = $request->get('journals') ?? [];
        $clearedJournals = [];
        $clearedIds      = $request->get('cleared') ?? [];
        $journals        = [];
        // Collect all submitted journals
        if (count($selectedIds) > 0) {
            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);
            $collector->setJournalIds($selectedIds);
            $journals  = $collector->getExtractedJournals();
        }

        // Collect all journals already reconciled
        if (count($clearedIds) > 0) {
            /** @var GroupCollectorInterface $collector */
            $collector       = app(GroupCollectorInterface::class);
            $collector->setJournalIds($clearedIds);
            $clearedJournals = $collector->getExtractedJournals();
        }

        /** @var array $journal */
        foreach ($journals as $journal) {
            $amount = $this->processJournal($account, $accountCurrency, $journal, $amount);
        }
        app('log')->debug(sprintf('Final amount is %s', $amount));

        /** @var array $journal */
        foreach ($clearedJournals as $journal) {
            if ($journal['date'] <= $end) {
                $clearedAmount = $this->processJournal($account, $accountCurrency, $journal, $clearedAmount);
            }
        }
        Log::debug(sprintf('Start balance: "%s"', $startBalance));
        Log::debug(sprintf('End balance: "%s"', $endBalance));
        Log::debug(sprintf('Cleared amount: "%s"', $clearedAmount));
        Log::debug(sprintf('Amount: "%s"', $amount));
        $difference      = bcadd(bcadd(bcsub($startBalance ?? '0', $endBalance ?? '0'), $clearedAmount), $amount);
        $diffCompare     = bccomp($difference, '0');
        $countCleared    = count($clearedJournals);
        $reconSum        = bcadd(bcadd($startBalance ?? '0', $amount), $clearedAmount);

        try {
            $view = view('accounts.reconcile.overview', compact('account', 'start', 'diffCompare', 'difference', 'end', 'clearedAmount', 'startBalance', 'endBalance', 'amount', 'route', 'countCleared', 'reconSum', 'selectedIds'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('View error: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $view = sprintf('Could not render accounts.reconcile.overview: %s', $e->getMessage());

            throw new FireflyException($view, 0, $e);
        }

        $return          = ['post_url' => $route, 'html' => $view];

        return response()->json($return);
    }

    private function processJournal(Account $account, TransactionCurrency $currency, array $journal, string $amount): string
    {
        $toAdd  = '0';
        app('log')->debug(sprintf('User submitted %s #%d: "%s"', $journal['transaction_type_type'], $journal['transaction_journal_id'], $journal['description']));

        // not much magic below we need to cover using tests.

        if ($account->id === $journal['source_account_id']) {
            if ($currency->id === $journal['currency_id']) {
                $toAdd = $journal['amount'];
            }
            if (null !== $journal['foreign_currency_id'] && $journal['foreign_currency_id'] === $currency->id) {
                $toAdd = $journal['foreign_amount'];
            }
        }
        if ($account->id === $journal['destination_account_id']) {
            if ($currency->id === $journal['currency_id']) {
                $toAdd = bcmul($journal['amount'], '-1');
            }
            if (null !== $journal['foreign_currency_id'] && $journal['foreign_currency_id'] === $currency->id) {
                $toAdd = bcmul($journal['foreign_amount'], '-1');
            }
        }

        app('log')->debug(sprintf('Going to add %s to %s', $toAdd, $amount));
        $amount = bcadd($amount, $toAdd);
        app('log')->debug(sprintf('Result is %s', $amount));

        return $amount;
    }

    /**
     * Returns a list of transactions in a modal.
     *
     * @return JsonResponse
     *
     * @throws FireflyException
     */
    public function transactions(Account $account, ?Carbon $start = null, ?Carbon $end = null)
    {
        if (null === $start || null === $end) {
            throw new FireflyException('Invalid dates submitted.');
        }
        if ($end->lt($start)) {
            [$end, $start] = [$start, $end];
        }
        $start->startOfDay();
        $end->endOfDay();
        $startDate      = clone $start;
        $startDate->subDay();

        $currency       = $this->accountRepos->getAccountCurrency($account) ?? $this->defaultCurrency;
        $startBalance   = Steam::finalAccountBalance($account, $startDate)['balance'];
        $endBalance     = Steam::finalAccountBalance($account, $end)['balance'];

        // get the transactions
        $selectionStart = clone $start;
        $selectionStart->subDays(3);
        $selectionEnd   = clone $end;
        $selectionEnd->addDays(3);

        // grab transactions:
        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);

        $collector->setAccounts(new Collection([$account]))
            ->setRange($selectionStart, $selectionEnd)
            ->withBudgetInformation()->withCategoryInformation()->withAccountInformation()
        ;
        $array          = $collector->getExtractedJournals();
        $journals       = $this->processTransactions($account, $array);

        try {
            $html = view(
                'accounts.reconcile.transactions',
                compact('account', 'journals', 'currency', 'start', 'end', 'selectionStart', 'selectionEnd')
            )->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            $html = sprintf('Could not render accounts.reconcile.transactions: %s', $e->getMessage());

            throw new FireflyException($html, 0, $e);
        }

        return response()->json(['html' => $html, 'startBalance' => $startBalance, 'endBalance' => $endBalance]);
    }

    /**
     * "fix" amounts to make it easier on the reconciliation overview:
     */
    private function processTransactions(Account $account, array $array): array
    {
        $journals = [];

        /** @var array $journal */
        foreach ($array as $journal) {
            $inverse    = false;

            if (TransactionTypeEnum::DEPOSIT->value === $journal['transaction_type_type']) {
                $inverse = true;
            }
            // transfer to this account? then positive amount:
            if (TransactionTypeEnum::TRANSFER->value === $journal['transaction_type_type'] && $account->id === $journal['destination_account_id']) {
                $inverse = true;
            }

            // opening balance into account? then positive amount:
            if (TransactionTypeEnum::OPENING_BALANCE->value === $journal['transaction_type_type']
                && $account->id === $journal['destination_account_id']) {
                $inverse = true;
            }

            if (true === $inverse) {
                $journal['amount'] = app('steam')->positive($journal['amount']);
                if (null !== $journal['foreign_amount']) {
                    $journal['foreign_amount'] = app('steam')->positive($journal['foreign_amount']);
                }
            }

            $journals[] = $journal;
        }

        return $journals;
    }
}
