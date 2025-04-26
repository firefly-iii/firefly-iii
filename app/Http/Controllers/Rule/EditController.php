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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RenderPartialViews;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\Support\Search\OperatorQuerySearch;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use RenderPartialViews;
    use RuleManagement;

    private RuleRepositoryInterface $ruleRepos;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->ruleRepos = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a rule.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function edit(Request $request, Rule $rule)
    {
        $triggerCount   = 0;
        $actionCount    = 0;
        $oldActions     = [];
        $oldTriggers    = [];

        // build triggers from query, if present.
        $query          = (string) $request->get('from_query');
        if ('' !== $query) {
            $search        = app(SearchInterface::class);
            $search->parseQuery($query);
            $words         = $search->getWords();
            $excludedWords = $search->getExcludedWords();
            $operators     = $search->getOperators()->toArray();
            if (count($words) > 0) {
                session()->flash('warning', trans('firefly.rule_from_search_words', ['string' => implode('', $words)]));
                foreach ($words as $word) {
                    $operators[] = [
                        'type'  => 'description_contains',
                        'value' => $word,
                    ];
                }
            }
            if (count($excludedWords) > 0) {
                session()->flash('warning', trans('firefly.rule_from_search_words', ['string' => implode('', $excludedWords)]));
                foreach ($excludedWords as $excludedWord) {
                    $operators[] = [
                        'type'  => '-description_contains',
                        'value' => $excludedWord,
                    ];
                }
            }
            $oldTriggers   = $this->parseFromOperators($operators);
        }
        // has old input?
        if (null !== $request->old() && is_array($request->old()) && count($request->old()) > 0) {
            $oldTriggers = $this->getPreviousTriggers($request);
            $oldActions  = $this->getPreviousActions($request);
        }
        $triggerCount   = count($oldTriggers);
        $actionCount    = count($oldActions);

        // overrule old input and query data when it has no rule data:
        if (0 === $triggerCount && 0 === $actionCount) {
            $oldTriggers  = $this->getCurrentTriggers($rule);
            $triggerCount = count($oldTriggers);
            $oldActions   = $this->getCurrentActions($rule);
            $actionCount  = count($oldActions);
        }

        $hasOldInput    = null !== $request->old('_token');
        $preFilled      = [
            'active'          => $hasOldInput ? (bool) $request->old('active') : $rule->active,
            'stop_processing' => $hasOldInput ? (bool) $request->old('stop_processing') : $rule->stop_processing,
            'strict'          => $hasOldInput ? (bool) $request->old('strict') : $rule->strict,
        ];

        // get rule trigger for update / store-journal:
        $primaryTrigger = $this->ruleRepos->getPrimaryTrigger($rule);
        $subTitle       = (string) trans('firefly.edit_rule', ['nr' => $rule->order,'title' => $rule->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('rules.edit.fromUpdate')) {
            $this->rememberPreviousUrl('rules.edit.url');
        }
        session()->forget('rules.edit.fromUpdate');

        $request->session()->flash('preFilled', $preFilled);

        return view('rules.rule.edit', compact('rule', 'subTitle', 'primaryTrigger', 'oldTriggers', 'oldActions', 'triggerCount', 'actionCount'));
    }

    /**
     * @throws FireflyException
     */
    private function parseFromOperators(array $submittedOperators): array
    {
        // TODO this code is duplicate.
        $operators       = config('search.operators');
        $renderedEntries = [];
        $triggers        = [];
        foreach ($operators as $key => $operator) {
            if ('user_action' !== $key && false === $operator['alias']) {
                $triggers[$key] = (string) trans(sprintf('firefly.rule_trigger_%s_choice', $key));
            }
        }
        asort($triggers);

        $index           = 0;
        foreach ($submittedOperators as $operator) {
            try {
                $renderedEntries[] = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => OperatorQuerySearch::getRootOperator($operator['type']),
                        'oldValue'   => $operator['value'],
                        'oldChecked' => false,
                        'count'      => $index + 1,
                        'triggers'   => $triggers,
                    ]
                )->render();
            } catch (\Throwable $e) {
                $message = sprintf('Throwable was thrown in getPreviousTriggers(): %s', $e->getMessage());
                app('log')->debug($message);
                app('log')->error($e->getTraceAsString());

                throw new FireflyException($message, 0, $e);
            }
            ++$index;
        }

        return $renderedEntries;
    }

    /**
     * Update the rule.
     *
     * @return Redirector|RedirectResponse
     */
    public function update(RuleFormRequest $request, Rule $rule)
    {
        $data     = $request->getRuleData();

        $this->ruleRepos->update($rule, $data);

        session()->flash('success', (string) trans('firefly.updated_rule', ['title' => $rule->title]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUrl('rules.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            session()->put('rules.edit.fromUpdate', true);

            $redirect = redirect(route('rules.edit', [$rule->id]))->withInput(['return_to_edit' => 1]);
        }

        return $redirect;
    }
}
