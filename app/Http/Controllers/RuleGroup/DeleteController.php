<?php

/**
 * DeleteController.php
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
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var RuleGroupRepositoryInterface */
    private $repository;

    /**
     * DeleteController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->repository = app(RuleGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete a rule group.
     *
     * @return Factory|View
     */
    public function delete(RuleGroup $ruleGroup)
    {
        $subTitle = (string) trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]);

        // put previous url in session
        $this->rememberPreviousUrl('rule-groups.delete.url');

        return view('rules.rule-group.delete', compact('ruleGroup', 'subTitle'));
    }

    /**
     * Actually destroy the rule group.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, RuleGroup $ruleGroup)
    {
        $title  = $ruleGroup->title;

        /** @var RuleGroup $moveTo */
        $moveTo = $this->repository->find((int) $request->get('move_rules_before_delete'));
        $this->repository->destroy($ruleGroup, $moveTo);

        session()->flash('success', (string) trans('firefly.deleted_rule_group', ['title' => $title]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('rule-groups.delete.url'));
    }
}
