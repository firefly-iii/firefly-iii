<?php
/**
 * CreateController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Rule;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;

use FireflyIII\Support\Http\Controllers\RuleManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    use RuleManagement;
    /** @var BillRepositoryInterface Bill repository */
    private $billRepos;
    /** @var RuleRepositoryInterface Rule repository */
    private $ruleRepos;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->billRepos = app(BillRepositoryInterface::class);
                $this->ruleRepos = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new rule. It will be stored under the given $ruleGroup.
     *
     * TODO remove bill from this method, move to separate routine.
     *
     * @param Request   $request
     * @param RuleGroup $ruleGroup
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create(Request $request, RuleGroup $ruleGroup)
    {
        $this->createDefaultRuleGroup();
        $this->createDefaultRule();
        $bill         = null;
        $billId       = (int)$request->get('fromBill');
        $preFilled    = [
            'strict' => true,
        ];
        $oldTriggers  = [];
        $oldActions   = [];
        $returnToBill = false;

        if ('true' === $request->get('return')) {
            $returnToBill = true;
        }

        // has bill?
        if ($billId > 0) {
            $bill = $this->billRepos->find($billId);
        }

        // has old input?
        if ($request->old()) {
            $oldTriggers = $this->getPreviousTriggers($request);
            $oldActions  = $this->getPreviousActions($request);
        }
        // has existing bill refered to in URI?
        if (null !== $bill && !$request->old()) {

            // create some sensible defaults:
            $preFilled['title']       = (string)trans('firefly.new_rule_for_bill_title', ['name' => $bill->name]);
            $preFilled['description'] = (string)trans('firefly.new_rule_for_bill_description', ['name' => $bill->name]);


            // get triggers and actions for bill:
            $oldTriggers = $this->getTriggersForBill($bill);
            $oldActions  = $this->getActionsForBill($bill);
        }

        $triggerCount = \count($oldTriggers);
        $actionCount  = \count($oldActions);
        $subTitleIcon = 'fa-clone';
        $subTitle     = (string)trans('firefly.make_new_rule', ['title' => $ruleGroup->title]);

        $request->session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rules.create.fromStore')) {
            $this->rememberPreviousUri('rules.create.uri');
        }
        session()->forget('rules.create.fromStore');

        return view(
            'rules.rule.create',
            compact(
                'subTitleIcon', 'oldTriggers', 'returnToBill', 'preFilled', 'bill', 'oldActions', 'triggerCount', 'actionCount', 'ruleGroup',
                'subTitle'
            )
        );
    }

    /**
     * Store the new rule.
     *
     * @param RuleFormRequest $request
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function store(RuleFormRequest $request)
    {
        $data = $request->getRuleData();
        $rule = $this->ruleRepos->store($data);
        session()->flash('success', (string)trans('firefly.stored_new_rule', ['title' => $rule->title]));
        app('preferences')->mark();

        // redirect to show bill.
        if ('true' === $request->get('return_to_bill') && (int)$request->get('bill_id') > 0) {
            return redirect(route('bills.show', [(int)$request->get('bill_id')])); // @codeCoverageIgnore
        }

        // redirect to new bill creation.
        if ((int)$request->get('bill_id') > 0) {
            return redirect($this->getPreviousUri('bills.create.uri')); // @codeCoverageIgnore
        }

        $redirect = redirect($this->getPreviousUri('rules.create.uri'));

        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            session()->put('rules.create.fromStore', true);
            $redirect = redirect(route('rules.create', [$data['rule_group_id']]))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }

    /**
     * Get actions based on a bill.
     *
     * @param Bill $bill
     *
     * @return array
     */
    private function getActionsForBill(Bill $bill): array
    {
        try {
            $result = view(
                'rules.partials.action',
                [
                    'oldAction'  => 'link_to_bill',
                    'oldValue'   => $bill->name,
                    'oldChecked' => false,
                    'count'      => 1,
                ]
            )->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Throwable was thrown in getActionsForBill(): %s', $e->getMessage()));
            Log::error($e->getTraceAsString());
            $result = 'Could not render view. See log files.';
        }

        // @codeCoverageIgnoreEnd

        return [$result];
    }

    /**
     * Create fake triggers to match the bill's properties
     *
     * @param Bill $bill
     *
     * @return array
     */
    private function getTriggersForBill(Bill $bill): array
    {
        $result   = [];
        $triggers = ['currency_is', 'amount_more', 'amount_less', 'description_contains'];
        $values   = [
            $bill->transactionCurrency()->first()->name,
            round($bill->amount_min, 12),
            round($bill->amount_max, 12),
            $bill->name,
        ];
        foreach ($triggers as $index => $trigger) {
            try {
                $string = view(
                    'rules.partials.trigger',
                    [
                        'oldTrigger' => $trigger,
                        'oldValue'   => $values[$index],
                        'oldChecked' => false,
                        'count'      => $index + 1,
                    ]
                )->render();
            } catch (Throwable $e) {
                Log::debug(sprintf('Throwable was thrown in getTriggersForBill(): %s', $e->getMessage()));
                Log::debug($e->getTraceAsString());
                $string = '';
            }
            if ('' !== $string) {
                $result[] = $string;
            }
        }

        return $result;
    }
}