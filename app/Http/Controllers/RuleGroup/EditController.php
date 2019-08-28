<?php
declare(strict_types=1);
/**
 * EditController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class EditController
 */
class EditController extends Controller
{
    /** @var RuleGroupRepositoryInterface */
    private $repository;

    /**
     * EditController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->repository = app(RuleGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Move a rule group down.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(RuleGroup $ruleGroup)
    {
        $this->repository->moveDown($ruleGroup);

        return redirect(route('rules.index'));
    }


    /**
     * Edit a rule group.
     *
     * @param Request $request
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, RuleGroup $ruleGroup)
    {
        $subTitle = (string)trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]);

        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'active' => $hasOldInput ? (bool)$request->old('active') : $ruleGroup->active,
        ];


        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('rule-groups.edit.fromUpdate')) {
            $this->rememberPreviousUri('rule-groups.edit.uri');
        }
        session()->forget('rule-groups.edit.fromUpdate');
        session()->flash('preFilled', $preFilled);

        return view('rules.rule-group.edit', compact('ruleGroup', 'subTitle'));
    }

    /**
     * Move the rule group up.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     */
    public function up(RuleGroup $ruleGroup)
    {
        $this->repository->moveUp($ruleGroup);

        return redirect(route('rules.index'));
    }

    /**
     * Update the rule group.
     *
     * @param RuleGroupFormRequest $request
     * @param RuleGroup $ruleGroup
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleGroupFormRequest $request, RuleGroup $ruleGroup)
    {
        $data = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'active'      => 1 === (int)$request->input('active'),
        ];

        $this->repository->update($ruleGroup, $data);

        session()->flash('success', (string)trans('firefly.updated_rule_group', ['title' => $ruleGroup->title]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUri('rule-groups.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('rule-groups.edit.fromUpdate', true);

            $redirect = redirect(route('rule-groups.edit', [$ruleGroup->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return $redirect;
    }

}
