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
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Input;
use Preferences;
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
        if (Session::get('rule-groups.create.fromStore') !== true) {
            Session::put('rule-groups.create.url', URL::previous());
        }
        Session::forget('accounts.create.fromStore');
        Session::flash('gaEventCategory', 'rules');
        Session::flash('gaEventAction', 'create-rule-group');

        return view('rules.create-rule-group', compact('subTitleIcon', 'what', 'subTitle'));
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
            Session::put('rule-groups.create.fromStore', true);

            return redirect(route('rules.rule-group.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(Session::get('rule-groups.create.url'));
    }

    /**
     * @return View
     */
    public function index()
    {
        $ruleGroups = Auth::user()->ruleGroups()->with('rules')->get();

        return view('rules.index', compact('ruleGroups'));
    }

    /**
     * @param Rule $rule
     */
    public function upRule(Rule $rule)
    {

    }

    /**
     * @param RuleGroup $ruleGroup
     */
    public function editRuleGroup(RuleGroup $ruleGroup)
    {

    }
}
