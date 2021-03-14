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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class EditController
 */
class EditController extends Controller
{
    private AttachmentHelperInterface $attachments;
    private BillRepositoryInterface   $repository;

    /**
     * BillController constructor.
     *
     * @codeCoverageIgnore
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
     * @param Request $request
     * @param Bill    $bill
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Bill $bill)
    {
        $periods = [];
        /** @var array $billPeriods */
        $billPeriods = config('firefly.bill_periods');

        foreach ($billPeriods as $current) {
            $periods[$current] = (string) trans('firefly.' . $current);
        }

        $subTitle = (string) trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('bills.edit.fromUpdate')) {
            $this->rememberPreviousUri('bills.edit.uri');
        }

        $currency         = app('amount')->getDefaultCurrency();
        $bill->amount_min = round((float) $bill->amount_min, $currency->decimal_places);
        $bill->amount_max = round((float) $bill->amount_max, $currency->decimal_places);
        $rules            = $this->repository->getRulesForBill($bill);
        $defaultCurrency  = app('amount')->getDefaultCurrency();

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');

        $preFilled = [
            'notes'                   => $this->repository->getNoteText($bill),
            'transaction_currency_id' => $bill->transaction_currency_id,
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $bill->active,
            'object_group'            => $bill->objectGroups->first() ? $bill->objectGroups->first()->title : '',
        ];

        $request->session()->flash('preFilled', $preFilled);
        $request->session()->forget('bills.edit.fromUpdate');

        return prefixView('bills.edit', compact('subTitle', 'periods', 'rules', 'bill', 'defaultCurrency', 'preFilled'));
    }


    /**
     * Update a bill.
     *
     * @param BillUpdateRequest $request
     * @param Bill            $bill
     *
     * @return RedirectResponse
     */
    public function update(BillUpdateRequest $request, Bill $bill): RedirectResponse
    {
        $billData = $request->getBillData();
        $bill     = $this->repository->update($bill, $billData);

        $request->session()->flash('success', (string) trans('firefly.updated_bill', ['name' => $bill->name]));
        app('preferences')->mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($bill, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info',(string)trans('firefly.no_att_demo_user'));
        }

        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }
        $redirect = redirect($this->getPreviousUri('bills.edit.uri'));

        if (1 === (int) $request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.edit.fromUpdate', true);

            $redirect = redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
