<?php
/**
 * RuleController.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers;

use Auth;
use Config;
use ExpandedForm;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Input;
use Preferences;
use Response;
use Session;
use URL;
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
        View::share('title', trans('firefly.rules'));
        View::share('mainTitleIcon', 'fa-random');
    }

    /**
     * @return View
     */
    public function createRuleGroup()
    {
        $subTitleIcon = 'fa-clone';
        $subTitle     = trans('firefly.make_new_rule_group');

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('rules.rule-group.create.fromStore') !== true) {
            Session::put('rules.rule-group.create.url', URL::previous());
        }
        Session::forget('rules.rule-group.create.fromStore');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'create-rule-group');

        return view('rules.rule-group.create', compact('subTitleIcon', 'what', 'subTitle'));
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function storeRule(RuleFormRequest $request, RuleGroup $ruleGroup)
    {
        echo '<pre>';
        var_dump(Input::all());
        exit();
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function createRule(RuleGroup $ruleGroup)
    {
        // count for possible present previous entered triggers/actions.
        $triggerCount = 0;
        $actionCount  = 0;

        // collection of those triggers/actions.
        $oldTriggers = [];
        $oldActions  = [];

        // array of valid values for triggers.
        $ruleTriggers     = array_keys(Config::get('firefly.rule-triggers'));
        $possibleTriggers = [];
        foreach ($ruleTriggers as $key) {
            if ($key != 'user_action') {
                $possibleTriggers[$key] = trans('firefly.rule_trigger_' . $key . '_choice');
            }
        }
        unset($key, $ruleTriggers);

        // has old input?
        if (Input::old()) {
            // process old triggers.
            foreach (Input::old('rule-trigger') as $index => $entry) {
                $count = ($index + 1);
                $triggerCount++;
                $oldTrigger    = $entry;
                $oldValue      = Input::old('rule-trigger-value')[$index];
                $oldChecked    = isset(Input::old('rule-action-value')[$index]) ? true : false;
                $oldTriggers[] = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => $oldTrigger,
                        'oldValue'   => $oldValue,
                        'oldChecked' => $oldChecked,
                        'triggers'   => $possibleTriggers,
                        'count'      => $count
                    ]
                )->render();
            }
//            echo '<pre>';
//            var_dump(Input::old());
//            var_dump($oldTriggers);
//            exit;
        }


        $subTitleIcon = 'fa-clone';
        $subTitle     = trans('firefly.make_new_rule', ['title' => $ruleGroup->title]);

        // mandatory field: rule triggers on update-journal or store-journal.
        $journalTriggers = [
            'store-journal'  => trans('firefly.rule_trigger_store_journal'),
            'update-journal' => trans('firefly.rule_trigger_update_journal')
        ];


        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('rules.rule.create.fromStore') !== true) {
            Session::put('rules.rule.create.url', URL::previous());
        }
        Session::forget('rules.rule.create.fromStore');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'create-rule-group');

        return view('rules.rule.create', compact('subTitleIcon','oldTriggers', 'triggerCount', 'actionCount', 'ruleGroup', 'subTitle', 'journalTriggers'));
    }

    /**
     * @param RuleGroupFormRequest    $request
     * @param RuleRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function storeRuleGroup(RuleGroupFormRequest $request, RuleRepositoryInterface $repository)
    {
        $data = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'user'        => Auth::user()->id,
        ];

        $ruleGroup = $repository->storeRuleGroup($data);

        Session::flash('success', trans('firefly.created_new_rule_group', ['title' => $ruleGroup->title]));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('rules.rule-group.create.fromStore', true);

            return redirect(route('rules.rule-group.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(Session::get('rules.rule-group.create.url'));
    }


    /**
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function editRuleGroup(RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('rules.rule-group.edit.fromUpdate') !== true) {
            Session::put('rules.rule-group.edit.url', URL::previous());
        }
        Session::forget('rules.rule-group.edit.fromUpdate');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'edit-rule-group');

        return view('rules.rule-group.edit', compact('ruleGroup', 'subTitle'));

    }

    /**
     * @param RuleGroupFormRequest    $request
     * @param RuleRepositoryInterface $repository
     * @param RuleGroup               $ruleGroup
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function updateRuleGroup(RuleGroupFormRequest $request, RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $data = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'active'      => intval($request->input('active')) == 1,
        ];

        $repository->updateRuleGroup($ruleGroup, $data);

        Session::flash('success', trans('firefly.updated_rule_group', ['title' => $ruleGroup->title]));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('rules.rule-group.edit.fromUpdate', true);

            return redirect(route('rules.rule-group.edit', [$ruleGroup->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(Session::get('rules.rule-group.edit.url'));

    }

    /**
     * @param RuleGroup $budget
     *
     * @return \Illuminate\View\View
     */
    public function deleteRuleGroup(RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]);

        $ruleGroupList = Expandedform::makeSelectList($repository->getRuleGroups(), true);
        unset($ruleGroupList[$ruleGroup->id]);

        // put previous url in session
        Session::put('rules.rule-group.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'delete-rule-group');

        return view('rules.rule-group.delete', compact('ruleGroup', 'subTitle', 'ruleGroupList'));
    }

    /**
     * @param RuleGroup               $ruleGroup
     * @param RuleRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyRuleGroup(RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {

        $title  = $ruleGroup->title;
        $moveTo = Auth::user()->ruleGroups()->find(intval(Input::get('move_rules_before_delete')));

        $repository->destroyRuleGroup($ruleGroup, $moveTo);


        Session::flash('success', trans('firefly.deleted_rule_group', ['title' => $title]));
        Preferences::mark();


        return redirect(Session::get('rules.rule-group.delete.url'));
    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderRuleTriggers(RuleRepositoryInterface $repository, Rule $rule)
    {
        $ids = Input::get('triggers');
        if (is_array($ids)) {
            $repository->reorderRuleTriggers($rule, $ids);
        }

        return Response::json(true);

    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderRuleActions(RuleRepositoryInterface $repository, Rule $rule)
    {
        $ids = Input::get('actions');
        if (is_array($ids)) {
            $repository->reorderRuleActions($rule, $ids);
        }

        return Response::json(true);

    }


    /**
     * @return View
     */
    public function index()
    {
        $ruleGroups = Auth::user()
                          ->ruleGroups()
                          ->orderBy('order', 'ASC')
                          ->with(
                              [
                                  'rules'              => function ($query) {
                                      $query->orderBy('order', 'ASC');

                                  },
                                  'rules.ruleTriggers' => function ($query) {
                                      $query->orderBy('order', 'ASC');
                                  },
                                  'rules.ruleActions'  => function ($query) {
                                      $query->orderBy('order', 'ASC');
                                  },
                              ]
                          )->get();

        return view('rules.index', compact('ruleGroups'));
    }


    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upRule(RuleRepositoryInterface $repository, Rule $rule)
    {
        $repository->moveRuleUp($rule);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param Rule                    $rule
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function downRule(RuleRepositoryInterface $repository, Rule $rule)
    {
        $repository->moveRuleDown($rule);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param RuleGroup               $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upRuleGroup(RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveRuleGroupUp($ruleGroup);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleRepositoryInterface $repository
     * @param RuleGroup               $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function downRuleGroup(RuleRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveRuleGroupDown($ruleGroup);

        return redirect(route('rules.index'));

    }

}
