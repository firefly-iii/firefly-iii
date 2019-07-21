<?php
/**
 * ExecutionController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\RuleGroup;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Jobs\ExecuteRuleGroupOnExistingTransactions;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\RedirectResponse;

/**
 * Class ExecutionController
 */
class ExecutionController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * ExecutionController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Execute the given rulegroup on a set of existing transactions.
     *
     * @param SelectTransactionsRequest $request
     * @param RuleGroup $ruleGroup
     *
     * @return RedirectResponse
     * @throws \Exception
     */
    public function execute(SelectTransactionsRequest $request, RuleGroup $ruleGroup): RedirectResponse
    {
        // Get parameters specified by the user
        /** @var User $user */
        $user      = auth()->user();
        $accounts  = $this->repository->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleGroupOnExistingTransactions($ruleGroup);

        // Apply parameters to the job
        $job->setUser($user);
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        // Tell the user that the job is queued
        session()->flash('success', (string)trans('firefly.applied_rule_group_selection', ['title' => $ruleGroup->title]));

        return redirect()->route('rules.index');
    }

    /**
     * Select transactions to apply the group on.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function selectTransactions(RuleGroup $ruleGroup)
    {
        $first    = session('first')->format('Y-m-d');
        $today    = Carbon::now()->format('Y-m-d');
        $subTitle = (string)trans('firefly.apply_rule_group_selection', ['title' => $ruleGroup->title]);

        return view('rules.rule-group.select-transactions', compact('first', 'today', 'ruleGroup', 'subTitle'));
    }


}