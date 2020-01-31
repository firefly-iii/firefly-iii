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

namespace FireflyIII\Http\Controllers\Rule;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Illuminate\Http\RedirectResponse;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
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
     * Delete a given rule.
     *
     * @param Rule $rule
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function delete(Rule $rule)
    {
        $subTitle = (string)trans('firefly.delete_rule', ['title' => $rule->title]);

        // put previous url in session
        $this->rememberPreviousUri('rules.delete.uri');

        return view('rules.rule.delete', compact('rule', 'subTitle'));
    }

    /**
     * Actually destroy the given rule.
     *
     * @param Rule $rule
     *
     * @return RedirectResponse
     */
    public function destroy(Rule $rule): RedirectResponse
    {
        $title = $rule->title;
        $this->ruleRepos->destroy($rule);

        session()->flash('success', (string)trans('firefly.deleted_rule', ['title' => $title]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('rules.delete.uri'));
    }

}
