<?php

/**
 * CreateController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BillStoreRequest;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
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
                app('view')->share('title', (string)trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->repository  = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a new bill.
     *
     * @param Request $request
     *
     * @return Application|Factory|View
     */
    public function create(Request $request)
    {
        $periods = [];
        /** @var array $billPeriods */
        $billPeriods = config('firefly.bill_periods');
        foreach ($billPeriods as $current) {
            $periods[$current] = (string)trans('firefly.repeat_freq_' . $current);
        }
        $subTitle        = (string)trans('firefly.create_new_bill');
        $defaultCurrency = app('amount')->getDefaultCurrency();

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('bills.create.fromStore')) {
            $this->rememberPreviousUri('bills.create.uri');
        }
        $request->session()->forget('bills.create.fromStore');

        return prefixView('bills.create', compact('periods', 'subTitle', 'defaultCurrency'));
    }

    /**
     * Store a new bill.
     *
     * @param BillStoreRequest $request
     *
     * @return RedirectResponse
     *
     */
    public function store(BillStoreRequest $request): RedirectResponse
    {
        $billData           = $request->getBillData();
        $billData['active'] = true;
        try {
            $bill = $this->repository->store($billData);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            $request->session()->flash('error', (string)trans('firefly.bill_store_error'));

            return redirect(route('bills.create'))->withInput();
        }
        $request->session()->flash('success', (string)trans('firefly.stored_new_bill', ['name' => $bill->name]));
        app('preferences')->mark();

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($bill, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info', (string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }

        return redirect(route('rules.create-from-bill', [$bill->id]));
    }
}
