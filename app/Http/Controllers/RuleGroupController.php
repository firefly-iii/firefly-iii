<?php

namespace FireflyIII\Http\Controllers;

use Auth;
use ExpandedForm;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Input;
use Preferences;
use Session;
use URL;
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
        View::share('title', trans('firefly.rules'));
        View::share('mainTitleIcon', 'fa-random');
    }

    /**
     * @return View
     */
    public function create()
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
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository)
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
    public function edit(RuleGroup $ruleGroup)
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
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
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
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return View
     */
    public function delete(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
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
     * @param RuleGroupRepositoryInterface $repository
     *
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {

        $title  = $ruleGroup->title;
        $moveTo = Auth::user()->ruleGroups()->find(intval(Input::get('move_rules_before_delete')));

        $repository->destroyRuleGroup($ruleGroup, $moveTo);


        Session::flash('success', trans('firefly.deleted_rule_group', ['title' => $title]));
        Preferences::mark();


        return redirect(Session::get('rules.rule-group.delete.url'));
    }


    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function up(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveRuleGroupUp($ruleGroup);

        return redirect(route('rules.index'));

    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveRuleGroupDown($ruleGroup);

        return redirect(route('rules.index'));

    }

}