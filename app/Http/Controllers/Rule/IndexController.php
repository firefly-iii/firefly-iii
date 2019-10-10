<?php
/**
 * IndexController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Rule;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use RuleManagement;
    /** @var RuleGroupRepositoryInterface Rule group repository */
    private $ruleGroupRepos;
    /** @var RuleRepositoryInterface Rule repository. */
    private $ruleRepos;

    /**
     * RuleController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');
                $this->ruleGroupRepos = app(RuleGroupRepositoryInterface::class);
                $this->ruleRepos      = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Move rule down in list.
     *
     * @param Rule $rule
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(Rule $rule)
    {
        $this->ruleRepos->moveDown($rule);

        return redirect(route('rules.index'));
    }

    /**
     * Index of all rules and groups.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        $this->createDefaultRuleGroup();
        $this->createDefaultRule();
        $ruleGroups = $this->ruleGroupRepos->getRuleGroupsWithRules($user);

        return view('rules.index', compact('ruleGroups'));
    }

    /**
     * Stop action for reordering of rule actions.
     *
     * @param Request $request
     * @param Rule    $rule
     *
     * @return JsonResponse
     */
    public function reorderRuleActions(Request $request, Rule $rule): JsonResponse
    {
        $ids = $request->get('actions');
        if (is_array($ids)) {
            $this->ruleRepos->reorderRuleActions($rule, $ids);
        }

        return response()->json('true');
    }

    /**
     * Stop action for reordering of rule triggers.
     *
     * @param Request $request
     * @param Rule    $rule
     *
     * @return JsonResponse
     */
    public function reorderRuleTriggers(Request $request, Rule $rule): JsonResponse
    {
        $ids = $request->get('triggers');
        if (is_array($ids)) {
            $this->ruleRepos->reorderRuleTriggers($rule, $ids);
        }

        return response()->json('true');
    }


    /**
     * Move rule ip.
     *
     * @param Rule $rule
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function up(Rule $rule)
    {
        $this->ruleRepos->moveUp($rule);

        return redirect(route('rules.index'));
    }

}
