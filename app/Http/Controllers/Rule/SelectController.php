<?php

/**
 * SelectController.php
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

namespace FireflyIII\Http\Controllers\Rule;

use Throwable;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class SelectController.
 */
class SelectController extends Controller
{
    use RuleManagement;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                return $next($request);
            }
        );
    }

    /**
     * Execute the given rule on a set of existing transactions.
     */
    public function execute(SelectTransactionsRequest $request, Rule $rule): RedirectResponse
    {
        // Get parameters specified by the user
        /** @var User $user */
        $user          = auth()->user();
        $accounts      = implode(',', $request->get('accounts'));
        $startDate     = new Carbon($request->get('start'));
        $endDate       = new Carbon($request->get('end'));

        // create new rule engine:
        $newRuleEngine = app(RuleEngineInterface::class);
        $newRuleEngine->setUser($user);

        // add extra operators:
        $newRuleEngine->addOperator(['type' => 'date_after', 'value' => $startDate->format('Y-m-d')]);
        $newRuleEngine->addOperator(['type' => 'date_before', 'value' => $endDate->format('Y-m-d')]);
        $newRuleEngine->addOperator(['type' => 'account_id', 'value' => $accounts]);

        // set rules:
        $newRuleEngine->setRules(new Collection([$rule]));
        $newRuleEngine->fire();
        $resultCount   = $newRuleEngine->getResults();

        session()->flash('success', trans_choice('firefly.applied_rule_selection', $resultCount, ['title' => $rule->title]));

        return redirect()->route('rules.index');
    }

    /**
     * View to select transactions by a rule.
     */
    public function selectTransactions(Rule $rule): Factory|RedirectResponse|View
    {
        if (false === $rule->active) {
            session()->flash('warning', trans('firefly.cannot_fire_inactive_rules'));

            return redirect(route('rules.index'));
        }
        // does the user have shared accounts?
        $first    = session('first', today(config('app.timezone'))->subYear())->format('Y-m-d');
        $today    = today(config('app.timezone'))->format('Y-m-d');
        $subTitle = (string) trans('firefly.apply_rule_selection', ['title' => $rule->title]);

        return view('rules.rule.select-transactions', compact('first', 'today', 'rule', 'subTitle'));
    }

    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are passed along
     * using the URL parameters (GET), and are usually put there using a Javascript thing.
     *
     * @throws FireflyException
     */
    public function testTriggers(TestRuleFormRequest $request): JsonResponse
    {
        // build fake rule
        $rule               = new Rule();

        /** @var \Illuminate\Database\Eloquent\Collection<int, RuleTrigger> $triggers */
        $triggers           = new Collection();
        $rule->strict       = '1' === $request->get('strict');

        // build trigger array from response
        $textTriggers       = $this->getValidTriggerList($request);

        // warn if nothing.
        if (0 === count($textTriggers)) {
            return response()->json(['html' => '', 'warning' => (string) trans('firefly.warning_no_valid_triggers')]);
        }

        foreach ($textTriggers as $textTrigger) {
            $needsContext             = config(sprintf('search.operators.%s.needs_context', $textTrigger['type'])) ?? true;
            $trigger                  = new RuleTrigger();
            $trigger->trigger_type    = $textTrigger['type'];
            $trigger->trigger_value   = $textTrigger['value'];
            if (false === $needsContext) {
                $trigger->trigger_value = 'true';
            }
            $trigger->stop_processing = $textTrigger['stop_processing'];
            if ($textTrigger['prohibited']) {
                $trigger->trigger_type = sprintf('-%s', $textTrigger['type']);
            }
            $triggers->push($trigger);
        }

        $rule->ruleTriggers = $triggers;

        // create new rule engine:
        /** @var RuleEngineInterface $newRuleEngine */
        $newRuleEngine      = app(RuleEngineInterface::class);

        // set rules:
        $newRuleEngine->setRules(new Collection([$rule]));
        $newRuleEngine->setRefreshTriggers(false);
        $collection         = $newRuleEngine->find();
        $collection         = $collection->slice(0, 20);

        // Warn the user if only a subset of transactions is returned
        $warning            = '';
        if (0 === count($collection)) {
            $warning = (string) trans('firefly.warning_no_matching_transactions');
        }

        // Return json response
        $view               = 'ERROR, see logs.';

        try {
            $view = view('list.journals-array-tiny', ['groups' => $collection])->render();
        } catch (Throwable $exception) {
            app('log')->error(sprintf('Could not render view in testTriggers(): %s', $exception->getMessage()));
            app('log')->error($exception->getTraceAsString());
            $view = sprintf('Could not render list.journals-tiny: %s', $exception->getMessage());

            throw new FireflyException($view, 0, $exception);
        }

        return response()->json(['html' => $view, 'warning' => $warning]);
    }

    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are grabbed from
     * the rule itself.
     *
     * @throws FireflyException
     */
    public function testTriggersByRule(Rule $rule): JsonResponse
    {
        $triggers      = $rule->ruleTriggers;

        if (0 === count($triggers)) {
            return response()->json(['html' => '', 'warning' => (string) trans('firefly.warning_no_valid_triggers')]);
        }
        // create new rule engine:
        $newRuleEngine = app(RuleEngineInterface::class);

        // set rules:
        $newRuleEngine->setRules(new Collection([$rule]));
        $collection    = $newRuleEngine->find();
        $collection    = $collection->slice(0, 20);

        $warning       = '';
        if (0 === count($collection)) {
            $warning = (string) trans('firefly.warning_no_matching_transactions');
        }

        // Return json response
        $view          = 'ERROR, see logs.';

        try {
            $view = view('list.journals-array-tiny', ['groups' => $collection])->render();
        } catch (Throwable $exception) {
            $message = sprintf('Could not render view in testTriggersByRule(): %s', $exception->getMessage());
            app('log')->error($message);
            app('log')->error($exception->getTraceAsString());

            throw new FireflyException($message, 0, $exception);
        }

        return response()->json(['html' => $view, 'warning' => $warning]);
    }
}
