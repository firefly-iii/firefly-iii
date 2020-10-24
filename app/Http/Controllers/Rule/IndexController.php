<?php
/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Rule;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use RuleManagement;

    private RuleGroupRepositoryInterface $ruleGroupRepos;
    private RuleRepositoryInterface      $ruleRepos;

    /**
     * RuleController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');
                $this->ruleGroupRepos = app(RuleGroupRepositoryInterface::class);
                $this->ruleRepos      = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Index of all rules and groups.
     *
     * @return Factory|View
     */
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        $this->createDefaultRuleGroup();
        $this->createDefaultRule();
        $this->ruleGroupRepos->resetRuleGroupOrder();
        $ruleGroups = $this->ruleGroupRepos->getRuleGroupsWithRules($user);

        return view('rules.index', compact('ruleGroups'));
    }

    /**
     * @param Rule $rule
     * @return RedirectResponse
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function search(Rule $rule): RedirectResponse
    {
        $route = route('search.index');
        $query = $this->ruleRepos->getSearchQuery($rule);
        $route = sprintf('%s?%s', $route, http_build_query(['search' => $query, 'rule' => $rule->id]));

        return redirect($route);
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
     * @param Request   $request
     * @param Rule      $rule
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function moveRule(Request $request, Rule $rule, RuleGroup $ruleGroup): JsonResponse
    {
        $order = (int) $request->get('order');
        $this->ruleRepos->moveRule($rule, $ruleGroup, (int) $order);

        return response()->json([]);
    }

}
