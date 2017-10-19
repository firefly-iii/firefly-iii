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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Preferences;
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
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.create_new_bill');


        // put previous url in session if not redirect from store (not "create another").
        if (session('bills.create.fromStore') !== true) {
            $this->rememberPreviousUri('bills.create.uri');
        }
        $request->session()->forget('bills.create.fromStore');
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'create');

        return view('bills.create', compact('periods', 'subTitle'));
    }

    /**
     * @param Request $request
     * @param Bill    $bill
     *
     * @return View
     */
    public function delete(Request $request, Bill $bill)
    {
        // put previous url in session
        $this->rememberPreviousUri('bills.delete.uri');
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'delete');
        $subTitle = trans('firefly.delete_bill', ['name' => $bill->name]);

        return view('bills.delete', compact('bill', 'subTitle'));
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        $name = $bill->name;
        $repository->destroy($bill);

        $request->session()->flash('success', strval(trans('firefly.deleted_bill', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('bills.delete.uri'));
    }

    /**
     * @param Request $request
     * @param Bill    $bill
     *
     * @return View
     */
    public function edit(Request $request, Bill $bill)
    {
        $periods = [];
        foreach (config('firefly.bill_periods') as $current) {
            $periods[$current] = trans('firefly.' . $current);
        }
        $subTitle = trans('firefly.edit_bill', ['name' => $bill->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('bills.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('bills.edit.uri');
        }

        $currency         = app('amount')->getDefaultCurrency();
        $bill->amount_min = round($bill->amount_min, $currency->decimal_places);
        $bill->amount_max = round($bill->amount_max, $currency->decimal_places);

        $request->session()->forget('bills.edit.fromUpdate');
        $request->session()->flash('gaEventCategory', 'bills');
        $request->session()->flash('gaEventAction', 'edit');

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
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function rescan(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        if (intval($bill->active) === 0) {
            $request->session()->flash('warning', strval(trans('firefly.cannot_scan_inactive_bill')));

            return redirect(URL::previous());
        }

        $journals = $repository->getPossiblyRelatedJournals($bill);
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $repository->scan($bill, $journal);
        }


        $request->session()->flash('success', strval(trans('firefly.rescanned_bill')));
        Preferences::mark();

        return redirect(URL::previous());
    }

    /**
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return View
     */
    public function show(Request $request, BillRepositoryInterface $repository, Bill $bill)
    {
        /** @var Carbon $date */
        $date           = session('start');
        $year           = $date->year;
        $page           = intval($request->get('page'));
        $pageSize       = intval(Preferences::get('transactionPageSize', 50)->data);
        $yearAverage    = $repository->getYearAverage($bill, $date);
        $overallAverage = $repository->getOverallAverage($bill);

        // use collector:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->setLimit($pageSize)->setPage($page)->withBudgetInformation()
                  ->withCategoryInformation();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath(route('bills.show', [$bill->id]));

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
        $request->session()->flash('success', strval(trans('firefly.stored_new_bill', ['name' => $bill->name])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.create.fromStore', true);

            return redirect(route('bills.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('bills.create.uri'));

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

        $request->session()->flash('success', strval(trans('firefly.updated_bill', ['name' => $bill->name])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('bills.edit.fromUpdate', true);

            return redirect(route('bills.edit', [$bill->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('bills.edit.uri'));

    }

}
