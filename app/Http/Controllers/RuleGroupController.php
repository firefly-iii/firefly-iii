<?php
/**
 * RuleGroupController.php
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
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Jobs\ExecuteRuleGroupOnExistingTransactions;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;
use Session;
use View;

/**
 * Class RuleGroupController
 *
 * @package FireflyIII\Http\Controllers
 */
class RuleGroupController extends Controller
{
    /**
     * RuleGroupController constructor.
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
     * @return View
     */
    public function create()
    {
        $subTitleIcon = 'fa-clone';
        $subTitle     = trans('firefly.make_new_rule_group');

        // put previous url in session if not redirect from store (not "create another").
        if (session('rule-groups.create.fromStore') !== true) {
            $this->rememberPreviousUri('rule-groups.create.uri');
        }
        Session::forget('rule-groups.create.fromStore');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'create-rule-group');

        return view('rules.rule-group.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return View
     */
    public function delete(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]);

        $ruleGroupList = ExpandedForm::makeSelectListWithEmpty($repository->get());
        unset($ruleGroupList[$ruleGroup->id]);

        // put previous url in session
        $this->rememberPreviousUri('rule-groups.delete.uri');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'delete-rule-group');

        return view('rules.rule-group.delete', compact('ruleGroup', 'subTitle', 'ruleGroupList'));
    }

    /**
     * @param Request                      $request
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {

        $title  = $ruleGroup->title;
        $moveTo = auth()->user()->ruleGroups()->find(intval($request->get('move_rules_before_delete')));

        $repository->destroy($ruleGroup, $moveTo);


        Session::flash('success', strval(trans('firefly.deleted_rule_group', ['title' => $title])));
        Preferences::mark();

        return redirect($this->getPreviousUri('rule-groups.delete.uri'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveDown($ruleGroup);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function edit(RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('rule-groups.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('rule-groups.edit.uri');
        }
        Session::forget('rule-groups.edit.fromUpdate');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'edit-rule-group');

        return view('rules.rule-group.edit', compact('ruleGroup', 'subTitle'));

    }

    /**
     * Execute the given rulegroup on a set of existing transactions
     *
     * @param SelectTransactionsRequest  $request
     * @param AccountRepositoryInterface $repository
     * @param RuleGroup                  $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function execute(SelectTransactionsRequest $request, AccountRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        // Get parameters specified by the user
        $accounts  = $repository->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleGroupOnExistingTransactions($ruleGroup);

        // Apply parameters to the job
        $job->setUser(auth()->user());
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        // Tell the user that the job is queued
        Session::flash('success', strval(trans('firefly.executed_group_on_existing_transactions', ['title' => $ruleGroup->title])));

        return redirect()->route('rules.index');
    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param RuleGroup                  $ruleGroup
     *
     * @return View
     */
    public function selectTransactions(AccountRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        // does the user have shared accounts?
        $accounts        = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $accountList     = ExpandedForm::makeSelectList($accounts);
        $checkedAccounts = array_keys($accountList);
        $first           = session('first')->format('Y-m-d');
        $today           = Carbon::create()->format('Y-m-d');
        $subTitle        = (string)trans('firefly.execute_on_existing_transactions');

        return view('rules.rule-group.select-transactions', compact('checkedAccounts', 'accountList', 'first', 'today', 'ruleGroup', 'subTitle'));
    }

    /**
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository)
    {
        $data      = $request->getRuleGroupData();
        $ruleGroup = $repository->store($data);

        Session::flash('success', strval(trans('firefly.created_new_rule_group', ['title' => $ruleGroup->title])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('rule-groups.create.fromStore', true);

            return redirect(route('rule-groups.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('rule-groups.create.uri'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function up(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveUp($ruleGroup);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $data = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'active'      => intval($request->input('active')) == 1,
        ];

        $repository->update($ruleGroup, $data);

        Session::flash('success', strval(trans('firefly.updated_rule_group', ['title' => $ruleGroup->title])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            Session::put('rule-groups.edit.fromUpdate', true);

            return redirect(route('rule-groups.edit', [$ruleGroup->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('rule-groups.edit.uri'));

    }
}
