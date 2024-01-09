<?php

/**
 * EditController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Bill;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BillUpdateRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface $attachments;
    private BillRepositoryInterface   $repository;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->repository  = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a bill.
     *
     * @return Factory|View
     */
    public function edit(Request $request, Bill $bill)
    {
        $periods          = [];

        /** @var array $billPeriods */
        $billPeriods      = config('firefly.bill_periods');

        foreach ($billPeriods as $current) {
            $periods[$current] = (string) trans('firefly.'.$current);
        }

        $subTitle         = (string) trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('bills.edit.fromUpdate')) {
            $this->rememberPreviousUrl('bills.edit.url');
        }

        $currency         = app('amount')->getDefaultCurrency();
        $bill->amount_min = app('steam')->bcround($bill->amount_min, $currency->decimal_places);
        $bill->amount_max = app('steam')->bcround($bill->amount_max, $currency->decimal_places);
        $rules            = $this->repository->getRulesForBill($bill);
        $defaultCurrency  = app('amount')->getDefaultCurrency();

        // code to handle active-checkboxes
        $hasOldInput      = null !== $request->old('_token');

        $preFilled        = [
            'bill_end_date'           => $bill->end_date,
            'extension_date'          => $bill->extension_date,
            'notes'                   => $this->repository->getNoteText($bill),
            'transaction_currency_id' => $bill->transaction_currency_id,
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $bill->active,
            'object_group'            => null !== $bill->objectGroups->first() ? $bill->objectGroups->first()->title : '',
        ];

        $request->session()->flash('preFilled', $preFilled);
        $request->session()->forget('bills.edit.fromUpdate');

        return view('bills.edit', compact('subTitle', 'periods', 'rules', 'bill', 'defaultCurrency', 'preFilled'));
    }

    /**
     * Update a bill.
     */
    public function update(BillUpdateRequest $request, Bill $bill): RedirectResponse
    {
        $billData = $request->getBillData();
        $bill     = $this->repository->update($bill, $billData);

        Log::channel('audit')->info(sprintf('Updated bill #%d.', $bill->id), $billData);

        $request->session()->flash('success', (string) trans('firefly.updated_bill', ['name' => $bill->name]));
        app('preferences')->mark();

        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($bill, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        $redirect = redirect($this->getPreviousUrl('bills.edit.url'));

        if (1 === (int) $request->get('return_to_edit')) {
            $request->session()->put('bills.edit.fromUpdate', true);

            $redirect = redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
        }

        return $redirect;
    }
}
