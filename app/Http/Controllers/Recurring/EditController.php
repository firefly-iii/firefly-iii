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

namespace FireflyIII\Http\Controllers\Recurring;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgets;
    /** @var RecurringRepositoryInterface Recurring repository */
    private $recurring;

    /**
     * EditController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string)trans('firefly.recurrences'));
                app('view')->share('subTitle', (string)trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);
                $this->budgets   = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a recurring transaction.
     *
     * @param Request $request
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \FireflyIII\Exceptions\FireflyException
     *
     */
    public function edit(Request $request, Recurrence $recurrence)
    {
        /** @var RecurrenceTransformer $transformer */
        $transformer = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag);

        $array   = $transformer->transform($recurrence);
        $budgets = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());

        /** @var RecurrenceRepetition $repetition */
        $repetition     = $recurrence->recurrenceRepetitions()->first();
        $currentRepType = $repetition->repetition_type;
        if ('' !== $repetition->repetition_moment) {
            $currentRepType .= ',' . $repetition->repetition_moment; // @codeCoverageIgnore
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('recurrences.edit.fromUpdate')) {
            $this->rememberPreviousUri('recurrences.edit.uri');
        }
        $request->session()->forget('recurrences.edit.fromUpdate');

        $repetitionEnd  = 'forever';
        $repetitionEnds = [
            'forever'    => (string)trans('firefly.repeat_forever'),
            'until_date' => (string)trans('firefly.repeat_until_date'),
            'times'      => (string)trans('firefly.repeat_times'),
        ];
        if (null !== $recurrence->repeat_until) {
            $repetitionEnd = 'until_date'; // @codeCoverageIgnore
        }
        if ($recurrence->repetitions > 0) {
            $repetitionEnd = 'times'; // @codeCoverageIgnore
        }

        $weekendResponses = [
            RecurrenceRepetition::WEEKEND_DO_NOTHING    => (string)trans('firefly.do_nothing'),
            RecurrenceRepetition::WEEKEND_SKIP_CREATION => (string)trans('firefly.skip_transaction'),
            RecurrenceRepetition::WEEKEND_TO_FRIDAY     => (string)trans('firefly.jump_to_friday'),
            RecurrenceRepetition::WEEKEND_TO_MONDAY     => (string)trans('firefly.jump_to_monday'),
        ];

        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'transaction_type'          => strtolower($recurrence->transactionType->type),
            'active'                    => $hasOldInput ? (bool)$request->old('active') : $recurrence->active,
            'apply_rules'               => $hasOldInput ? (bool)$request->old('apply_rules') : $recurrence->apply_rules,
            'deposit_source_id'         => $array['transactions'][0]['source_id'],
            'withdrawal_destination_id' => $array['transactions'][0]['destination_id'],
        ];

        $array['transactions'][0]['tags'] = implode(',', $array['transactions'][0]['tags'] ?? []);

        return view(
            'recurring.edit',
            compact('recurrence', 'array', 'weekendResponses', 'budgets', 'preFilled', 'currentRepType', 'repetitionEnd', 'repetitionEnds')
        );
    }

    /**
     * Update the recurring transaction.
     *
     * @param RecurrenceFormRequest $request
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function update(RecurrenceFormRequest $request, Recurrence $recurrence)
    {
        $data = $request->getAll();

        $this->recurring->update($recurrence, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_recurrence', ['title' => $recurrence->title]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUri('recurrences.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('recurrences.edit.fromUpdate', true);

            $redirect = redirect(route('recurring.edit', [$recurrence->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return $redirect;
    }


}
