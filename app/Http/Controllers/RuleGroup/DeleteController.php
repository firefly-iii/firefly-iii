<?php
declare(strict_types=1);
/**
 * DeleteController.php
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
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var RuleGroupRepositoryInterface */
    private $repository;

    /**
     * DeleteController constructor.
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
     * Delete a rule group.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(RuleGroup $ruleGroup)
    {
        $subTitle = (string)trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]);

        // put previous url in session
        $this->rememberPreviousUri('rule-groups.delete.uri');

        return view('rules.rule-group.delete', compact('ruleGroup', 'subTitle'));
    }

    /**
     * Actually destroy the rule group.
     *
     * @param Request $request
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, RuleGroup $ruleGroup)
    {
        $title = $ruleGroup->title;

        /** @var RuleGroup $moveTo */
        $moveTo = $this->repository->find((int)$request->get('move_rules_before_delete'));
        $this->repository->destroy($ruleGroup, $moveTo);

        session()->flash('success', (string)trans('firefly.deleted_rule_group', ['title' => $title]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('rule-groups.delete.uri'));
    }

}
