<?php
/**
 * BillController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Preferences;
use Session;
use URL;
use View;

/**
 * Class BillController
 *
 * @package FireflyIII\Http\Controllers
 */
class BillController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.bills'));
                View::share('mainTitleIcon', 'fa-calendar-o');

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function create()
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.create_new_bill');


        // put previous url in session if not redirect from store (not "create another").
        if (session('bills.create.fromStore') !== true) {
            Session::put('bills.create.url', URL::previous());
        }
        Session::forget('bills.create.fromStore');
        Session::flash('gaEventCategory', 'bills');
        Session::flash('gaEventAction', 'create');

        return view('bills.create', compact('periods', 'subTitle'));
    }

    /**
     * @param Bill $bill
     *
     * @return View
     */
    public function delete(Bill $bill)
    {
        // put previous url in session
        Session::put('bills.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'bills');
        Session::flash('gaEventAction', 'delete');
        $subTitle = trans('firefly.delete_bill', ['name' => $bill->name]);

        return view('bills.delete', compact('bill', 'subTitle'));
    }

    /**
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BillRepositoryInterface $repository, Bill $bill)
    {
        $name = $bill->name;
        $repository->destroy($bill);

        Session::flash('success', strval(trans('firefly.deleted_bill', ['name' => $name])));
        Preferences::mark();

        return redirect(session('bills.delete.url'));
    }

    /**
     * @param Bill $bill
     *
     * @return View
     */
    public function edit(Bill $bill)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('bills.edit.fromUpdate') !== true) {
            Session::put('bills.edit.url', URL::previous());
        }
        Session::forget('bills.edit.fromUpdate');
        Session::flash('gaEventCategory', 'bills');
        Session::flash('gaEventAction', 'edit');

        return view('bills.edit', compact('subTitle', 'periods', 'bill'));
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return View
     */
    public function index(BillRepositoryInterface $repository)
    {
        /** @var Carbon $start */
        $start = session('start');
        /** @var Carbon $end */
        $end = session('end');

        $bills = $repository->getBills();
        $bills->each(
            function (Bill $bill) use ($repository, $start, $end) {

                // paid in this period?
                $bill->paidDates = $repository->getPaidDatesInRange($bill, $start, $end);
                $bill->payDates  = $repository->getPayDatesInRange($bill, $start, $end);
                $lastDate        = clone $start;
                if ($bill->paidDates->count() >= $bill->payDates->count()) {
                    $lastDate = $end;
                }
                $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill, $lastDate);
            }
        );

        return view('bills.index', compact('bills'));
    }

    /**
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rescan(BillRepositoryInterface $repository, Bill $bill)
    {
        if (intval($bill->active) == 0) {
            Session::flash('warning', strval(trans('firefly.cannot_scan_inactive_bill')));

            return redirect(URL::previous());
        }

        $journals = $repository->getPossiblyRelatedJournals($bill);
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $repository->scan($bill, $journal);
        }


        Session::flash('success', strval(trans('firefly.rescanned_bill')));
        Preferences::mark();

        return redirect(URL::previous());
    }

    /**
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return View
     */
    public function show(BillRepositoryInterface $repository, Bill $bill)
    {
        /** @var Carbon $date */
        $date           = session('start');
        $year           = $date->year;
        $page           = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $pageSize       = intval(Preferences::get('transactionPageSize', 50)->data);
        $yearAverage    = $repository->getYearAverage($bill, $date);
        $overallAverage = $repository->getOverallAverage($bill);

        // use collector:
        $collector  = new JournalCollector(auth()->user());
        $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->setPage($page)->setLimit($pageSize);
        $journals   = $collector->getPaginatedJournals();
        $journals->setPath('/bills/show/' . $bill->id);

        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill, new Carbon);
        $hideBill                = true;
        $subTitle                = e($bill->name);

        return view('bills.show', compact('journals', 'yearAverage', 'overallAverage', 'year', 'hideBill', 'bill', 'subTitle'));
    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->store($billData);
        Session::flash('success', strval(trans('firefly.stored_new_bill', ['name' => e($bill->name)])));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('bills.create.fromStore', true);

            return redirect(route('bills.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('bills.create.url'));

    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BillFormRequest $request, BillRepositoryInterface $repository, Bill $bill)
    {
        $billData = $request->getBillData();
        $bill     = $repository->update($bill, $billData);

        Session::flash('success', strval(trans('firefly.updated_bill', ['name' => e($bill->name)])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('bills.edit.fromUpdate', true);

            return redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('bills.edit.url'));

    }

}
