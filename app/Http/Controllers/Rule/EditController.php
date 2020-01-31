<?php
/**
 * EditController.php
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
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RenderPartialViews;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use RuleManagement, RenderPartialViews;

    /** @var RuleRepositoryInterface Rule repository */
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

                $this->ruleRepos = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a rule.
     *
     * @param Request $request
     * @param Rule    $rule
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Rule $rule)
    {
        $triggerCount = 0;
        $actionCount  = 0;
        $oldActions   = [];
        $oldTriggers  = [];
        // has old input?
        if (count($request->old()) > 0) {
            $oldTriggers  = $this->getPreviousTriggers($request);
            $triggerCount = count($oldTriggers);
            $oldActions   = $this->getPreviousActions($request);
            $actionCount  = count($oldActions);
        }

        // overrule old input when it has no rule data:
        if (0 === $triggerCount && 0 === $actionCount) {
            $oldTriggers  = $this->getCurrentTriggers($rule);
            $triggerCount = count($oldTriggers);
            $oldActions   = $this->getCurrentActions($rule);
            $actionCount  = count($oldActions);
        }

        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'active'          => $hasOldInput ? (bool)$request->old('active') : $rule->active,
            'stop_processing' => $hasOldInput ? (bool)$request->old('stop_processing') : $rule->stop_processing,
            'strict'          => $hasOldInput ? (bool)$request->old('strict') : $rule->strict,

        ];

        // get rule trigger for update / store-journal:
        $primaryTrigger = $this->ruleRepos->getPrimaryTrigger($rule);
        $subTitle       = (string)trans('firefly.edit_rule', ['title' => $rule->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('rules.edit.fromUpdate')) {
            $this->rememberPreviousUri('rules.edit.uri');
        }
        session()->forget('rules.edit.fromUpdate');

        $request->session()->flash('preFilled', $preFilled);

        return view('rules.rule.edit', compact('rule', 'subTitle', 'primaryTrigger', 'oldTriggers', 'oldActions', 'triggerCount', 'actionCount'));
    }

    /**
     * Update the rule.
     *
     * @param RuleFormRequest $request
     * @param Rule            $rule
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleFormRequest $request, Rule $rule)
    {
        $data = $request->getRuleData();
        $this->ruleRepos->update($rule, $data);

        session()->flash('success', (string)trans('firefly.updated_rule', ['title' => $rule->title]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUri('rules.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('rules.edit.fromUpdate', true);

            $redirect = redirect(route('rules.edit', [$rule->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
