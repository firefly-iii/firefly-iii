<?php

/**
 * CreateController.php
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

namespace FireflyIII\Http\Controllers\RuleGroup;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var RuleGroupRepositoryInterface */
    private $repository;

    /**
     * CreateController constructor.
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
     * @return Factory|View
     */
    public function create()
    {
        $subTitleIcon = 'fa-clone';
        $subTitle     = (string)trans('firefly.make_new_rule_group');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rule-groups.create.fromStore')) {
            $this->rememberPreviousUrl('rule-groups.create.url');
        }
        session()->forget('rule-groups.create.fromStore');

        return view('rules.rule-group.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * Store the rule group.
     *
     * @return Redirector|RedirectResponse
     */
    public function store(RuleGroupFormRequest $request)
    {
        $data      = $request->getRuleGroupData();
        $ruleGroup = $this->repository->store($data);

        session()->flash('success', (string)trans('firefly.created_new_rule_group', ['title' => $ruleGroup->title]));
        app('preferences')->mark();

        $redirect  = redirect($this->getPreviousUrl('rule-groups.create.url'));
        if (1 === (int)$request->get('create_another')) {
            session()->put('rule-groups.create.fromStore', true);

            $redirect = redirect(route('rule-groups.create'))->withInput();
        }

        return $redirect;
    }
}
