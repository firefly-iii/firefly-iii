<?php
/**
 * ExecutionController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use Illuminate\Http\RedirectResponse;
use Log;

/**
 * Class ExecutionController
 */
class ExecutionController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $repository;

    /** @var RuleGroupRepositoryInterface */
    private $ruleGroupRepository;

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

                $this->repository          = app(AccountRepositoryInterface::class);
                $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);

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
        $accounts  = $this->repository->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // start looping.
        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser(auth()->user());

        $rules = [];
        /** @var Rule $rule */
        foreach ($this->ruleGroupRepository->getActiveRules($ruleGroup) as $rule) {
            $rules[] = $rule->id;
        }

        $ruleEngine->setRulesToApply($rules);
        $ruleEngine->setTriggerMode(RuleEngine::TRIGGER_STORE);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts);
        $collector->setRange($startDate, $endDate);
        $journals = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($journals as $journal) {
            Log::debug('Start of new journal.');
            $ruleEngine->processJournalArray($journal);
            Log::debug('Done with all rules for this group + done with journal.');
        }

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
