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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RecurrenceFormRequest;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 *
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgets;
    /** @var RecurringRepositoryInterface Recurring repository */
    private $recurring;

    private AttachmentHelperInterface $attachments;

    /**
     * CreateController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string) trans('firefly.recurrences'));
                app('view')->share('subTitle', (string) trans('firefly.create_new_recurrence'));

                $this->recurring   = app(RecurringRepositoryInterface::class);
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new recurring transaction.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {
        $budgets           = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $tomorrow          = new Carbon;
        $oldRepetitionType = $request->old('repetition_type');
        $tomorrow->addDay();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('recurring.create.fromStore')) {
            $this->rememberPreviousUri('recurring.create.uri');
        }
        $request->session()->forget('recurring.create.fromStore');
        $repetitionEnds   = [
            'forever'    => (string) trans('firefly.repeat_forever'),
            'until_date' => (string) trans('firefly.repeat_until_date'),
            'times'      => (string) trans('firefly.repeat_times'),
        ];
        $weekendResponses = [
            RecurrenceRepetition::WEEKEND_DO_NOTHING    => (string) trans('firefly.do_nothing'),
            RecurrenceRepetition::WEEKEND_SKIP_CREATION => (string) trans('firefly.skip_transaction'),
            RecurrenceRepetition::WEEKEND_TO_FRIDAY     => (string) trans('firefly.jump_to_friday'),
            RecurrenceRepetition::WEEKEND_TO_MONDAY     => (string) trans('firefly.jump_to_monday'),
        ];


        $hasOldInput = null !== $request->old('_token'); // flash some data
        $preFilled   = [
            'first_date'       => $tomorrow->format('Y-m-d'),
            'transaction_type' => $hasOldInput ? $request->old('transaction_type') : 'withdrawal',
            'active'           => $hasOldInput ? (bool) $request->old('active') : true,
            'apply_rules'      => $hasOldInput ? (bool) $request->old('apply_rules') : true,
        ];
        $request->session()->flash('preFilled', $preFilled);

        return view(
            'recurring.create',
            compact('tomorrow', 'oldRepetitionType', 'weekendResponses', 'preFilled', 'repetitionEnds', 'defaultCurrency', 'budgets')
        );
    }


    /**
     * Store a recurring transaction.
     *
     * @param RecurrenceFormRequest $request
     *
     * @return RedirectResponse|Redirector
     */
    public function store(RecurrenceFormRequest $request)
    {
        $data = $request->getAll();
        try {
            $recurrence = $this->recurring->store($data);
        } catch (FireflyException $e) {
            session()->flash('error', $e->getMessage());

            return redirect(route('recurring.create'))->withInput();
        }

        $request->session()->flash('success', (string) trans('firefly.stored_new_recurrence', ['title' => $recurrence->title]));
        app('preferences')->mark();

        // store attachment(s):
        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($recurrence, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        $redirect = redirect($this->getPreviousUri('recurring.create.uri'));
        if (1 === (int) $request->get('create_another')) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('recurring.create.fromStore', true);

            $redirect = redirect(route('recurring.create'))->withInput();
        }

        // redirect to previous URL.
        return $redirect;
    }
}
