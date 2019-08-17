<?php
declare(strict_types=1);
/**
 * CreateController.php
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
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var RuleGroupRepositoryInterface */
    private $repository;

    /**
     * CreateController constructor.
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
     * Create a new rule group.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitleIcon = 'fa-clone';
        $subTitle     = (string)trans('firefly.make_new_rule_group');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rule-groups.create.fromStore')) {
            $this->rememberPreviousUri('rule-groups.create.uri');
        }
        session()->forget('rule-groups.create.fromStore');

        return view('rules.rule-group.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * Store the rule group.
     *
     * @param RuleGroupFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(RuleGroupFormRequest $request)
    {
        $data      = $request->getRuleGroupData();
        $ruleGroup = $this->repository->store($data);

        session()->flash('success', (string)trans('firefly.created_new_rule_group', ['title' => $ruleGroup->title]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('rule-groups.create.uri'));
        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            session()->put('rule-groups.create.fromStore', true);

            $redirect = redirect(route('rule-groups.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
