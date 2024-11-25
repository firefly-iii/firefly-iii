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

namespace FireflyIII\Http\Controllers\Recurring;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private AttachmentHelperInterface    $attachments;
    private BillRepositoryInterface      $billRepository;
    private BudgetRepositoryInterface    $budgetRepos;
    private RecurringRepositoryInterface $recurring;

    /**
     * CreateController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string)trans('firefly.recurrences'));
                app('view')->share('subTitle', (string)trans('firefly.create_new_recurrence'));

                $this->recurring      = app(RecurringRepositoryInterface::class);
                $this->budgetRepos    = app(BudgetRepositoryInterface::class);
                $this->attachments    = app(AttachmentHelperInterface::class);
                $this->billRepository = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new recurring transaction.
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $budgets           = app('expandedform')->makeSelectListWithEmpty($this->budgetRepos->getActiveBudgets());
        $bills             = app('expandedform')->makeSelectListWithEmpty($this->billRepository->getActiveBills());
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $tomorrow          = today(config('app.timezone'));
        $oldRepetitionType = $request->old('repetition_type');
        $tomorrow->addDay();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('recurring.create.fromStore')) {
            $this->rememberPreviousUrl('recurring.create.url');
        }
        $request->session()->forget('recurring.create.fromStore');
        $repetitionEnds    = [
            'forever'    => (string)trans('firefly.repeat_forever'),
            'until_date' => (string)trans('firefly.repeat_until_date'),
            'times'      => (string)trans('firefly.repeat_times'),
        ];
        $weekendResponses  = [
            RecurrenceRepetition::WEEKEND_DO_NOTHING    => (string)trans('firefly.do_nothing'),
            RecurrenceRepetition::WEEKEND_SKIP_CREATION => (string)trans('firefly.skip_transaction'),
            RecurrenceRepetition::WEEKEND_TO_FRIDAY     => (string)trans('firefly.jump_to_friday'),
            RecurrenceRepetition::WEEKEND_TO_MONDAY     => (string)trans('firefly.jump_to_monday'),
        ];
        $hasOldInput       = null !== $request->old('_token'); // flash some data
        $preFilled         = [
            'first_date'       => $tomorrow->format('Y-m-d'),
            'transaction_type' => $hasOldInput ? $request->old('transaction_type') : 'withdrawal',
            'active'           => $hasOldInput ? (bool)$request->old('active') : true,
            'apply_rules'      => $hasOldInput ? (bool)$request->old('apply_rules') : true,
        ];
        $request->session()->flash('preFilled', $preFilled);

        return view(
            'recurring.create',
            compact('tomorrow', 'oldRepetitionType', 'bills', 'weekendResponses', 'preFilled', 'repetitionEnds', 'defaultCurrency', 'budgets')
        );
    }

    /**
     * @return Factory|\Illuminate\Contracts\View\View
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createFromJournal(Request $request, TransactionJournal $journal)
    {
        $budgets           = app('expandedform')->makeSelectListWithEmpty($this->budgetRepos->getActiveBudgets());
        $bills             = app('expandedform')->makeSelectListWithEmpty($this->billRepository->getActiveBills());
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $tomorrow          = today(config('app.timezone'));
        $oldRepetitionType = $request->old('repetition_type');
        $tomorrow->addDay();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('recurring.create.fromStore')) {
            $this->rememberPreviousUrl('recurring.create.url');
        }
        $request->session()->forget('recurring.create.fromStore');
        $repetitionEnds    = [
            'forever'    => (string)trans('firefly.repeat_forever'),
            'until_date' => (string)trans('firefly.repeat_until_date'),
            'times'      => (string)trans('firefly.repeat_times'),
        ];
        $weekendResponses  = [
            RecurrenceRepetition::WEEKEND_DO_NOTHING    => (string)trans('firefly.do_nothing'),
            RecurrenceRepetition::WEEKEND_SKIP_CREATION => (string)trans('firefly.skip_transaction'),
            RecurrenceRepetition::WEEKEND_TO_FRIDAY     => (string)trans('firefly.jump_to_friday'),
            RecurrenceRepetition::WEEKEND_TO_MONDAY     => (string)trans('firefly.jump_to_monday'),
        ];

        // fill prefilled with journal info
        $type              = strtolower($journal->transactionType->type);

        /** @var Transaction $source */
        $source            = $journal->transactions()->where('amount', '<', 0)->first();

        /** @var Transaction $dest */
        $dest              = $journal->transactions()->where('amount', '>', 0)->first();
        $category          = null !== $journal->categories()->first() ? $journal->categories()->first()->name : '';
        $budget            = null !== $journal->budgets()->first() ? $journal->budgets()->first()->id : 0;
        $bill              = null !== $journal->bill ? $journal->bill->id : 0;
        $hasOldInput       = null !== $request->old('_token'); // flash some data
        $preFilled         = [];
        if (true === $hasOldInput) {
            $preFilled = [
                'title'                     => $request->old('title'),
                'transaction_description'   => $request->old('description'),
                'transaction_currency_id'   => $request->old('transaction_currency_id'),
                'amount'                    => $request->old('amount'),
                'foreign_currency_id'       => $request->old('foreign_currency_id'),
                'foreign_amount'            => $request->old('foreign_amount'),
                'source_id'                 => $request->old('source_id'),
                'deposit_source_id'         => $request->old('deposit_source_id'),
                'destination_id'            => $request->old('destination_id'),
                'withdrawal_destination_id' => $request->old('withdrawal_destination_id'),
                'first_date'                => $request->old('first_date'),
                'transaction_type'          => $request->old('transaction_type'),
                'category'                  => $request->old('category'),
                'budget_id'                 => $request->old('budget_id'),
                'bill_id'                   => $request->old('bill_id'),
                'active'                    => (bool)$request->old('active'),
                'apply_rules'               => (bool)$request->old('apply_rules'),
            ];
        }
        if (false === $hasOldInput) {
            $preFilled = [
                'title'                     => $journal->description,
                'transaction_description'   => $journal->description,
                'transaction_currency_id'   => $journal->transaction_currency_id,
                'amount'                    => $dest->amount,
                'foreign_currency_id'       => $dest->foreign_currency_id,
                'foreign_amount'            => $dest->foreign_amount,
                'source_id'                 => $source->account_id,
                'deposit_source_id'         => $source->account_id,
                'destination_id'            => $dest->account_id,
                'withdrawal_destination_id' => $dest->account_id,
                'first_date'                => $tomorrow->format('Y-m-d'),
                'transaction_type'          => $type,
                'category'                  => $category,
                'budget_id'                 => $budget,
                'bill_id'                   => $bill,
                'active'                    => true,
                'apply_rules'               => true,
            ];
        }
        $request->session()->flash('preFilled', $preFilled);

        return view(
            'recurring.create',
            compact('tomorrow', 'oldRepetitionType', 'bills', 'weekendResponses', 'preFilled', 'repetitionEnds', 'defaultCurrency', 'budgets')
        );
    }

    /**
     * Store a recurring transaction.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function store(RecurrenceFormRequest $request)
    {
        $data     = $request->getAll();

        try {
            $recurrence = $this->recurring->store($data);
        } catch (FireflyException $e) {
            session()->flash('error', $e->getMessage());

            return redirect(route('recurring.create'))->withInput();
        }
        Log::channel('audit')->info('Stored new recurrence.', $data);

        $request->session()->flash('success', (string)trans('firefly.stored_new_recurrence', ['title' => $recurrence->title]));
        app('preferences')->mark();

        // store attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($recurrence, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        $redirect = redirect($this->getPreviousUrl('recurring.create.url'));
        if (1 === (int)$request->get('create_another')) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('recurring.create.fromStore', true);

            $redirect = redirect(route('recurring.create'))->withInput();
        }

        // redirect to previous URL.
        return $redirect;
    }
}
