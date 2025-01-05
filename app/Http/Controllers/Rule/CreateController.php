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

namespace FireflyIII\Http\Controllers\Rule;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    use ModelInformation;
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
     * Create a new rule. It will be stored under the given $ruleGroup.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function create(Request $request, ?RuleGroup $ruleGroup = null)
    {
        $this->createDefaultRuleGroup();
        $preFilled    = [
            'strict' => true,
        ];
        $oldTriggers  = [];
        $oldActions   = [];

        // build triggers from query, if present.
        $query        = (string) $request->get('from_query');
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
        // var_dump($oldTriggers);exit;

        // restore actions and triggers from old input:
        if (is_array($request->old()) && count($request->old()) > 0) {
            $oldTriggers = $this->getPreviousTriggers($request);
            $oldActions  = $this->getPreviousActions($request);
        }

        $triggerCount = count($oldTriggers);
        $actionCount  = count($oldActions);
        $subTitleIcon = 'fa-clone';

        // title depends on whether or not there is a rule group:
        $subTitle     = (string) trans('firefly.make_new_rule_no_group');
        if (null !== $ruleGroup) {
            $subTitle = (string) trans('firefly.make_new_rule', ['title' => $ruleGroup->title]);
        }

        // flash old data
        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rules.create.fromStore')) {
            $this->rememberPreviousUrl('rules.create.url');
        }
        session()->forget('rules.create.fromStore');

        return view(
            'rules.rule.create',
            compact('subTitleIcon', 'oldTriggers', 'preFilled', 'oldActions', 'triggerCount', 'actionCount', 'ruleGroup', 'subTitle')
        );
    }

    /**
     * Create a new rule. It will be stored under the given $ruleGroup.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function createFromBill(Request $request, Bill $bill)
    {
        $request->session()->flash('info', (string) trans('firefly.instructions_rule_from_bill', ['name' => e($bill->name)]));

        $this->createDefaultRuleGroup();
        $preFilled    = [
            'strict'      => true,
            'title'       => (string) trans('firefly.new_rule_for_bill_title', ['name' => $bill->name]),
            'description' => (string) trans('firefly.new_rule_for_bill_description', ['name' => $bill->name]),
        ];

        // make triggers and actions from the bill itself.

        // get triggers and actions for bill:
        $oldTriggers  = $this->getTriggersForBill($bill);
        $oldActions   = $this->getActionsForBill($bill);

        // restore actions and triggers from old input:
        if (null !== $request->old() && is_array($request->old()) && count($request->old()) > 0) {
            $oldTriggers = $this->getPreviousTriggers($request);
            $oldActions  = $this->getPreviousActions($request);
        }

        $triggerCount = count($oldTriggers);
        $actionCount  = count($oldActions);
        $subTitleIcon = 'fa-clone';

        // title depends on whether there is a rule group:
        $subTitle     = (string) trans('firefly.make_new_rule_no_group');

        // flash old data
        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rules.create.fromStore')) {
            $this->rememberPreviousUrl('rules.create.url');
        }
        session()->forget('rules.create.fromStore');

        return view(
            'rules.rule.create',
            compact('subTitleIcon', 'oldTriggers', 'preFilled', 'oldActions', 'triggerCount', 'actionCount', 'subTitle')
        );
    }

    /**
     * @return Factory|\Illuminate\Contracts\View\View
     *
     * @throws FireflyException
     */
    public function createFromJournal(Request $request, TransactionJournal $journal)
    {
        $request->session()->flash('info', (string) trans('firefly.instructions_rule_from_journal', ['name' => e($journal->description)]));

        $subTitleIcon = 'fa-clone';
        $subTitle     = (string) trans('firefly.make_new_rule_no_group');

        // get triggers and actions for journal.
        $oldTriggers  = $this->getTriggersForJournal($journal);
        $oldActions   = [];

        $this->createDefaultRuleGroup();

        // collect pre-filled information:
        $preFilled    = [
            'strict'      => true,
            'title'       => (string) trans('firefly.new_rule_for_journal_title', ['description' => $journal->description]),
            'description' => (string) trans('firefly.new_rule_for_journal_description', ['description' => $journal->description]),
        ];

        // restore actions and triggers from old input:
        if (null !== $request->old() && is_array($request->old()) && count($request->old()) > 0) {
            $oldTriggers = $this->getPreviousTriggers($request);
            $oldActions  = $this->getPreviousActions($request);
        }

        $triggerCount = count($oldTriggers);
        $actionCount  = count($oldActions);

        // flash old data
        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rules.create.fromStore')) {
            $this->rememberPreviousUrl('rules.create.url');
        }
        session()->forget('rules.create.fromStore');

        return view(
            'rules.rule.create',
            compact('subTitleIcon', 'oldTriggers', 'preFilled', 'oldActions', 'triggerCount', 'actionCount', 'subTitle')
        );
    }

    public function duplicate(Request $request): JsonResponse
    {
        $ruleId = (int) $request->get('id');
        $rule   = $this->ruleRepos->find($ruleId);
        if (null !== $rule) {
            $this->ruleRepos->duplicate($rule);
        }

        return new JsonResponse(['OK']);
    }

    /**
     * Store the new rule.
     *
     * @return Redirector|RedirectResponse
     */
    public function store(RuleFormRequest $request)
    {
        $data     = $request->getRuleData();

        $rule     = $this->ruleRepos->store($data);
        session()->flash('success', (string) trans('firefly.stored_new_rule', ['title' => $rule->title]));
        app('preferences')->mark();

        // redirect to show bill.
        if ('true' === $request->get('return_to_bill') && (int) $request->get('bill_id') > 0) {
            return redirect(route('bills.show', [(int) $request->get('bill_id')]));
        }

        // redirect to new bill creation.
        if ((int) $request->get('bill_id') > 0) {
            return redirect($this->getPreviousUrl('bills.create.url'));
        }

        $redirect = redirect($this->getPreviousUrl('rules.create.url'));

        if (1 === (int) $request->get('create_another')) {
            session()->put('rules.create.fromStore', true);
            $redirect = redirect(route('rules.create', [$data['rule_group_id']]))->withInput();
        }

        return $redirect;
    }
}
