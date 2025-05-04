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

use FireflyIII\Enums\RecurrenceRepetitionWeekend;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface    $attachments;
    private BillRepositoryInterface      $billRepository;
    private BudgetRepositoryInterface    $budgetRepos;
    private RecurringRepositoryInterface $recurring;

    /**
     * EditController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string) trans('firefly.recurrences'));
                app('view')->share('subTitle', (string) trans('firefly.recurrences'));

                $this->recurring      = app(RecurringRepositoryInterface::class);
                $this->budgetRepos    = app(BudgetRepositoryInterface::class);
                $this->attachments    = app(AttachmentHelperInterface::class);
                $this->billRepository = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a recurring transaction.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function edit(Request $request, Recurrence $recurrence)
    {
        // TODO this should be in the repository.
        $count                            = $recurrence->recurrenceTransactions()->count();
        if (0 === $count) {
            throw new FireflyException('This recurring transaction has no meta-data. You will have to delete it and recreate it. Sorry!');
        }

        /** @var RecurrenceTransformer $transformer */
        $transformer                      = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $array                            = $transformer->transform($recurrence);
        $budgets                          = app('expandedform')->makeSelectListWithEmpty($this->budgetRepos->getActiveBudgets());
        $bills                            = app('expandedform')->makeSelectListWithEmpty($this->billRepository->getActiveBills());

        /** @var RecurrenceRepetition $repetition */
        $repetition                       = $recurrence->recurrenceRepetitions()->first();
        $currentRepType                   = $repetition->repetition_type;
        if ('' !== $repetition->repetition_moment) {
            $currentRepType = sprintf('%s,%s', $currentRepType,$repetition->repetition_moment);
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('recurrences.edit.fromUpdate')) {
            $this->rememberPreviousUrl('recurrences.edit.url');
        }
        $request->session()->forget('recurrences.edit.fromUpdate');

        $repetitionEnd                    = 'forever';
        $repetitionEnds                   = [
            'forever'    => (string) trans('firefly.repeat_forever'),
            'until_date' => (string) trans('firefly.repeat_until_date'),
            'times'      => (string) trans('firefly.repeat_times'),
        ];
        if (null !== $recurrence->repeat_until) {
            $repetitionEnd = 'until_date';
        }
        if ($recurrence->repetitions > 0) {
            $repetitionEnd = 'times';
        }

        $weekendResponses                 = [
            RecurrenceRepetitionWeekend::WEEKEND_DO_NOTHING->value    => (string) trans('firefly.do_nothing'),
            RecurrenceRepetitionWeekend::WEEKEND_SKIP_CREATION->value => (string) trans('firefly.skip_transaction'),
            RecurrenceRepetitionWeekend::WEEKEND_TO_FRIDAY->value     => (string) trans('firefly.jump_to_friday'),
            RecurrenceRepetitionWeekend::WEEKEND_TO_MONDAY->value     => (string) trans('firefly.jump_to_monday'),
        ];

        $hasOldInput                      = null !== $request->old('_token');
        $preFilled                        = [
            'transaction_type'          => strtolower($recurrence->transactionType->type),
            'active'                    => $hasOldInput ? (bool) $request->old('active') : $recurrence->active,
            'apply_rules'               => $hasOldInput ? (bool) $request->old('apply_rules') : $recurrence->apply_rules,
            'deposit_source_id'         => $array['transactions'][0]['source_id'],
            'withdrawal_destination_id' => $array['transactions'][0]['destination_id'],
        ];
        $array['first_date']              = substr((string) $array['first_date'], 0, 10);
        $array['repeat_until']            = substr((string) $array['repeat_until'], 0, 10);
        $array['transactions'][0]['tags'] = implode(',', $array['transactions'][0]['tags'] ?? []);

        return view(
            'recurring.edit',
            compact(
                'recurrence',
                'array',
                'bills',
                'weekendResponses',
                'budgets',
                'preFilled',
                'currentRepType',
                'repetitionEnd',
                'repetitionEnds'
            )
        );
    }

    /**
     * Update the recurring transaction.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function update(RecurrenceFormRequest $request, Recurrence $recurrence)
    {
        $data     = $request->getAll();
        $this->recurring->update($recurrence, $data);

        $request->session()->flash('success', (string) trans('firefly.updated_recurrence', ['title' => $recurrence->title]));
        Log::channel('audit')->info(sprintf('Updated recurrence #%d.', $recurrence->id), $data);

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($recurrence, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUrl('recurrences.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('recurrences.edit.fromUpdate', true);

            $redirect = redirect(route('recurring.edit', [$recurrence->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return $redirect;
    }
}
