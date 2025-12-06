<?php

/**
 * ExecutionController.php
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

namespace FireflyIII\Http\Controllers\RuleGroup;

use Carbon\Carbon;
use Exception;
use FireflyIII\Events\Model\TransactionGroup\TriggeredStoredTransactionGroup;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class ExecutionController
 */
class ExecutionController extends Controller
{
    private AccountRepositoryInterface $repository;

    /**
     * ExecutionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->repository = app(AccountRepositoryInterface::class);
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Execute the given rulegroup on a set of existing transactions.
     *
     * @throws Exception
     */
    public function execute(SelectTransactionsRequest $request, RuleGroup $ruleGroup): RedirectResponse
    {
        // Get parameters specified by the user
        $accounts = $request->get('accounts');
        $set      = $this->repository->getAccountsById($accounts);
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($set);
        // add date operators.
        if (null !== $request->get('start')) {
            $startDate = new Carbon($request->get('start'));
            $collector->setStart($startDate);
        }
        if (null !== $request->get('end')) {
            $endDate = new Carbon($request->get('end'));
            $collector->setEnd($endDate);
        }
        $final = $collector->getGroups();
        $ids   = $final->pluck('id')->toArray();
        Log::debug(sprintf('Found %d groups collected from %d account(s)', $final->count(), $set->count()));
        foreach (array_chunk($ids, 1337) as $setOfIds) {
            Log::debug(sprintf('Now processing %d groups', count($setOfIds)));
            $groups = TransactionGroup::whereIn('id', $setOfIds)->get();
            /** @var TransactionGroup $group */
            foreach ($groups as $group) {
                Log::debug(sprintf('Processing group #%d.', $group->id));
                event(new TriggeredStoredTransactionGroup($group));
            }
        }

        // Tell the user that the job is queued
        session()->flash('success', (string)trans('firefly.applied_rule_group_selection', ['title' => $ruleGroup->title]));

        return redirect()->route('rules.index');
    }

    /**
     * Select transactions to apply the group on.
     *
     * @return Factory|View
     */
    public function selectTransactions(RuleGroup $ruleGroup): Factory | \Illuminate\Contracts\View\View
    {
        $subTitle = (string)trans('firefly.apply_rule_group_selection', ['title' => $ruleGroup->title]);

        return view('rules.rule-group.select-transactions', ['ruleGroup' => $ruleGroup, 'subTitle' => $subTitle]);
    }
}
