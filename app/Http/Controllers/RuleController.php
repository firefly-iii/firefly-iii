<?php
/**
 * RuleController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use Illuminate\Http\Request;
use Preferences;
use Response;
use Session;
use View;

/**
 * Class RuleController
 *
 * @package FireflyIII\Http\Controllers
 */
class RuleController extends Controller
{
    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.rules'));
                View::share('mainTitleIcon', 'fa-random');

                return $next($request);
            }
        );
    }

    /**
     * Create a new rule. It will be stored under the given $ruleGroup.
     *
     * @param Request   $request
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function create(Request $request, RuleGroup $ruleGroup)
    {
        // count for possible present previous entered triggers/actions.
        $triggerCount = 0;
        $actionCount  = 0;

        // collection of those triggers/actions.
        $oldTriggers = [];
        $oldActions  = [];

        // has old input?
        if ($request->old()) {
            // process old triggers.
            $oldTriggers  = $this->getPreviousTriggers($request);
            $triggerCount = count($oldTriggers);

            // process old actions
            $oldActions  = $this->getPreviousActions($request);
            $actionCount = count($oldActions);
        }

        $subTitleIcon = 'fa-clone';
        $subTitle     = trans('firefly.make_new_rule', ['title' => $ruleGroup->title]);

        // put previous url in session if not redirect from store (not "create another").
        if (session('rules.create.fromStore') !== true) {
            $this->rememberPreviousUri('rules.create.uri');
        }
        Session::forget('rules.create.fromStore');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'create-rule');

        return view(
            'rules.rule.create', compact('subTitleIcon', 'oldTriggers', 'oldActions', 'triggerCount', 'actionCount', 'ruleGroup', 'subTitle')
        );
    }

    /**
     * Delete a given rule.
     *
     * @param Rule $rule
     *
     * @return View
     */
    public function delete(Rule $rule)
    {
        $subTitle = trans('firefly.delete_rule', ['title' => $rule->title]);

        // put previous url in session
        $this->rememberPreviousUri('rules.delete.uri');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'delete-rule');

        return view('rules.rule.delete', compact('rule', 'subTitle'));
    }

    /**
     * Actually destroy the given rule.
     *
     * @param Rule                    $rule
     * @param RuleRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RuleRepositoryInterface $repository, Rule $rule)
    {

        $title = $rule->title;
        $repository->destroy($rule);

        Session::flash('success', trans('firefly.deleted_rule', ['title' => $title]));
        Preferences::mark();

        return redirect($this->getPreviousUri('rules.delete.uri'));
    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(RuleRepositoryInterface $repository, Rule $rule)
    {
        $repository->moveDown($rule);

        return redirect(route('rules.index'));

    }

    /**
     * @param Request                 $request
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return View
     */
    public function edit(Request $request, RuleRepositoryInterface $repository, Rule $rule)
    {
        /** @var RuleGroupRepositoryInterface $ruleGroupRepository */
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroups          = ExpandedForm::makeSelectList($ruleGroupRepository->get());
        $triggerCount        = 0;
        $actionCount         = 0;
        $oldActions          = [];
        $oldTriggers         = [];
        // has old input?
        if (count($request->old()) > 0) {
            $oldTriggers  = $this->getPreviousTriggers($request);
            $triggerCount = count($oldTriggers);
            $oldActions   = $this->getPreviousActions($request);
            $actionCount  = count($oldActions);
        }

        // overrule old input when it as no rule data:
        if ($triggerCount === 0 && $actionCount === 0) {
            $oldTriggers  = $this->getCurrentTriggers($rule);
            $triggerCount = count($oldTriggers);
            $oldActions   = $this->getCurrentActions($rule);
            $actionCount  = count($oldActions);
        }

        // get rule trigger for update / store-journal:
        $primaryTrigger = $repository->getPrimaryTrigger($rule);
        $subTitle       = trans('firefly.edit_rule', ['title' => $rule->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('rules.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('rules.edit.uri');
        }
        Session::forget('rules.edit.fromUpdate');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'edit-rule');

        return view(
            'rules.rule.edit', compact(
                                 'rule', 'subTitle',
                                 'primaryTrigger', 'oldTriggers', 'oldActions', 'triggerCount', 'actionCount', 'ruleGroups'
                             )
        );
    }

    /**
     * Execute the given rule on a set of existing transactions
     *
     * @param SelectTransactionsRequest  $request
     * @param AccountRepositoryInterface $repository
     * @param Rule                       $rule
     *
     * @return \Illuminate\Http\RedirectResponse
     * @internal param RuleGroup $ruleGroup
     */
    public function execute(SelectTransactionsRequest $request, AccountRepositoryInterface $repository, Rule $rule)
    {
        // Get parameters specified by the user
        $accounts  = $repository->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleOnExistingTransactions($rule);

        // Apply parameters to the job
        $job->setUser(auth()->user());
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        // Tell the user that the job is queued
        Session::flash('success', strval(trans('firefly.applied_rule_selection', ['title' => $rule->title])));

        return redirect()->route('rules.index');
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     *
     * @return View
     */
    public function index(RuleGroupRepositoryInterface $repository)
    {
        $this->createDefaultRuleGroup();
        $this->createDefaultRule();
        $ruleGroups = $repository->getRuleGroupsWithRules(auth()->user());

        return view('rules.index', compact('ruleGroups'));
    }

    /**
     * @param Request                 $request
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderRuleActions(Request $request, RuleRepositoryInterface $repository, Rule $rule)
    {
        $ids = $request->get('actions');
        if (is_array($ids)) {
            $repository->reorderRuleActions($rule, $ids);
        }

        return Response::json('true');

    }

    /**
     * @param Request                 $request
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderRuleTriggers(Request $request, RuleRepositoryInterface $repository, Rule $rule)
    {
        $ids = $request->get('triggers');
        if (is_array($ids)) {
            $repository->reorderRuleTriggers($rule, $ids);
        }

        return Response::json('true');

    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param Rule                       $rule
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function selectTransactions(AccountRepositoryInterface $repository, Rule $rule)
    {
        // does the user have shared accounts?
        $accounts        = $repository->getAccountsByType([AccountType::ASSET]);
        $accountList     = ExpandedForm::makeSelectList($accounts);
        $checkedAccounts = array_keys($accountList);
        $first           = session('first')->format('Y-m-d');
        $today           = Carbon::create()->format('Y-m-d');
        $subTitle        = (string)trans('firefly.apply_rule_selection', ['title' => $rule->title]);

        return view('rules.rule.select-transactions', compact('checkedAccounts', 'accountList', 'first', 'today', 'rule', 'subTitle'));
    }

    /**
     * @param RuleFormRequest         $request
     * @param RuleRepositoryInterface $repository
     * @param RuleGroup               $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(RuleFormRequest $request, RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $data                  = $request->getRuleData();
        $data['rule_group_id'] = $ruleGroup->id;

        $rule = $repository->store($data);
        Session::flash('success', trans('firefly.stored_new_rule', ['title' => $rule->title]));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('rules.create.fromStore', true);

            return redirect(route('rules.create', [$ruleGroup]))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('rules.create.uri'));
    }

    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are passed along
     * using the URL parameters (GET), and are usually put there using a Javascript thing.
     *
     * This method will parse and validate those rules and create a "TransactionMatcher" which will attempt
     * to find transaction journals matching the users input. A maximum range of transactions to try (range) and
     * a maximum number of transactions to return (limit) are set as well.
     *
     * @param TestRuleFormRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testTriggers(TestRuleFormRequest $request)
    {
        // build trigger array from response
        $triggers = $this->getValidTriggerList($request);

        if (count($triggers) === 0) {
            return Response::json(['html' => '', 'warning' => trans('firefly.warning_no_valid_triggers')]);
        }

        $limit = config('firefly.test-triggers.limit');
        $range = config('firefly.test-triggers.range');

        /** @var TransactionMatcher $matcher */
        $matcher = app(TransactionMatcher::class);
        $matcher->setLimit($limit);
        $matcher->setRange($range);
        $matcher->setTriggers($triggers);
        $matchingTransactions = $matcher->findTransactionsByTriggers();

        // Warn the user if only a subset of transactions is returned
        $warning = '';
        if (count($matchingTransactions) === $limit) {
            $warning = trans('firefly.warning_transaction_subset', ['max_num_transactions' => $limit]);
        }
        if (count($matchingTransactions) === 0) {
            $warning = trans('firefly.warning_no_matching_transactions', ['num_transactions' => $range]);
        }

        // Return json response
        $view = view('list.journals-tiny', ['transactions' => $matchingTransactions])->render();

        return Response::json(['html' => $view, 'warning' => $warning]);
    }

    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are grabbed from
     * the rule itself.
     *
     * This method will parse and validate those rules and create a "TransactionMatcher" which will attempt
     * to find transaction journals matching the users input. A maximum range of transactions to try (range) and
     * a maximum number of transactions to return (limit) are set as well.
     *
     *
     * @param Rule $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testTriggersByRule(Rule $rule)
    {

        $triggers = $rule->ruleTriggers;

        if (count($triggers) === 0) {
            return Response::json(['html' => '', 'warning' => trans('firefly.warning_no_valid_triggers')]);
        }

        $limit = config('firefly.test-triggers.limit');
        $range = config('firefly.test-triggers.range');

        /** @var TransactionMatcher $matcher */
        $matcher = app(TransactionMatcher::class);
        $matcher->setLimit($limit);
        $matcher->setRange($range);
        $matcher->setRule($rule);
        $matchingTransactions = $matcher->findTransactionsByRule();

        // Warn the user if only a subset of transactions is returned
        $warning = '';
        if (count($matchingTransactions) === $limit) {
            $warning = trans('firefly.warning_transaction_subset', ['max_num_transactions' => $limit]);
        }
        if (count($matchingTransactions) === 0) {
            $warning = trans('firefly.warning_no_matching_transactions', ['num_transactions' => $range]);
        }

        // Return json response
        $view = view('list.journals-tiny', ['transactions' => $matchingTransactions])->render();

        return Response::json(['html' => $view, 'warning' => $warning]);
    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function up(RuleRepositoryInterface $repository, Rule $rule)
    {
        $repository->moveUp($rule);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param RuleFormRequest         $request
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleRepositoryInterface $repository, RuleFormRequest $request, Rule $rule)
    {
        $data = $request->getRuleData();
        $repository->update($rule, $data);

        Session::flash('success', trans('firefly.updated_rule', ['title' => $rule->title]));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('rules.edit.fromUpdate', true);

            return redirect(route('rules.edit', [$rule->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('rules.edit.uri'));
    }

    private function createDefaultRule()
    {
        /** @var RuleRepositoryInterface $repository */
        $repository = app(RuleRepositoryInterface::class);

        if ($repository->count() === 0) {
            $data = [
                'rule_group_id'       => $repository->getFirstRuleGroup()->id,
                'stop_processing'     => 0,
                'title'               => trans('firefly.default_rule_name'),
                'description'         => trans('firefly.default_rule_description'),
                'trigger'             => 'store-journal',
                'rule-trigger-values' => [
                    trans('firefly.default_rule_trigger_description'),
                    trans('firefly.default_rule_trigger_from_account'),
                ],
                'rule-action-values'  => [
                    trans('firefly.default_rule_action_prepend'),
                    trans('firefly.default_rule_action_set_category'),
                ],

                'rule-triggers' => ['description_is', 'from_account_is'],
                'rule-actions'  => ['prepend_description', 'set_category'],
            ];

            $repository->store($data);
        }

    }

    /**
     *
     */
    private function createDefaultRuleGroup()
    {

        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);

        if ($repository->count() === 0) {
            $data = [
                'title'       => trans('firefly.default_rule_group_name'),
                'description' => trans('firefly.default_rule_group_description'),
            ];

            $repository->store($data);
        }
    }

    /**
     * @param Rule $rule
     *
     * @return array
     */
    private function getCurrentActions(Rule $rule)
    {
        $index   = 0;
        $actions = [];

        /** @var RuleAction $entry */
        foreach ($rule->ruleActions as $entry) {
            $count     = ($index + 1);
            $actions[] = view(
                'rules.partials.action',
                [
                    'oldAction'  => $entry->action_type,
                    'oldValue'   => $entry->action_value,
                    'oldChecked' => $entry->stop_processing,
                    'count'      => $count,
                ]
            )->render();
            $index++;
        }

        return $actions;
    }

    /**
     * @param Rule $rule
     *
     * @return array
     */
    private function getCurrentTriggers(Rule $rule)
    {
        $index    = 0;
        $triggers = [];

        /** @var RuleTrigger $entry */
        foreach ($rule->ruleTriggers as $entry) {
            if ($entry->trigger_type !== 'user_action') {
                $count      = ($index + 1);
                $triggers[] = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => $entry->trigger_type,
                        'oldValue'   => $entry->trigger_value,
                        'oldChecked' => $entry->stop_processing,
                        'count'      => $count,
                    ]
                )->render();
                $index++;
            }
        }

        return $triggers;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getPreviousActions(Request $request)
    {
        $newIndex = 0;
        $actions  = [];
        /** @var array $oldActions */
        $oldActions = is_array($request->old('rule-action')) ? $request->old('rule-action') : [];
        foreach ($oldActions as $index => $entry) {
            $count     = ($newIndex + 1);
            $checked   = isset($request->old('rule-action-stop')[$index]) ? true : false;
            $actions[] = view(
                'rules.partials.action',
                [
                    'oldAction'  => $entry,
                    'oldValue'   => $request->old('rule-action-value')[$index],
                    'oldChecked' => $checked,
                    'count'      => $count,
                ]
            )->render();
            $newIndex++;
        }

        return $actions;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getPreviousTriggers(Request $request)
    {
        $newIndex = 0;
        $triggers = [];
        /** @var array $oldTriggers */
        $oldTriggers = is_array($request->old('rule-trigger')) ? $request->old('rule-trigger') : [];
        foreach ($oldTriggers as $index => $entry) {
            $count      = ($newIndex + 1);
            $oldChecked = isset($request->old('rule-trigger-stop')[$index]) ? true : false;
            $triggers[] = view(
                'rules.partials.trigger',
                [
                    'oldTrigger' => $entry,
                    'oldValue'   => $request->old('rule-trigger-value')[$index],
                    'oldChecked' => $oldChecked,
                    'count'      => $count,
                ]
            )->render();
            $newIndex++;
        }

        return $triggers;
    }

    /**
     * @param TestRuleFormRequest $request
     *
     * @return array
     */
    private function getValidTriggerList(TestRuleFormRequest $request): array
    {

        $triggers = [];
        $data     = [
            'rule-triggers'       => $request->get('rule-trigger'),
            'rule-trigger-values' => $request->get('rule-trigger-value'),
            'rule-trigger-stop'   => $request->get('rule-trigger-stop'),
        ];
        if (is_array($data['rule-triggers'])) {
            foreach ($data['rule-triggers'] as $index => $triggerType) {
                $data['rule-trigger-stop'][$index] = $data['rule-trigger-stop'][$index] ?? 0;
                $triggers[]                        = [
                    'type'           => $triggerType,
                    'value'          => $data['rule-trigger-values'][$index],
                    'stopProcessing' => intval($data['rule-trigger-stop'][$index]) === 1 ? true : false,
                ];
            }
        }

        return $triggers;
    }


}
