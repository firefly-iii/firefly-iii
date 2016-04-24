<?php namespace FireflyIII\Http\Controllers;

use Config;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
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
        View::share('title', trans('firefly.bills'));
        View::share('mainTitleIcon', 'fa-calendar-o');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $periods  = Config::get('firefly.periods_to_text');
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
     * @return \Illuminate\View\View
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
     * @return \Illuminate\View\View
     */
    public function edit(Bill $bill)
    {
        $periods  = Config::get('firefly.periods_to_text');
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
     * @return \Illuminate\View\View
     */
    public function index(BillRepositoryInterface $repository)
    {
        $start = session('start');
        $end   = session('end');

        $bills = $repository->getBills();
        $bills->each(
            function (Bill $bill) use ($repository, $start, $end) {
                $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
                $bill->lastFoundMatch    = $repository->lastFoundMatch($bill);
                $journals                = $repository->getJournalsInRange($bill, $start, $end);
                // loop journals, find average:
                $average = '0';
                $count   = $journals->count();
                if ($count > 0) {
                    $sum = '0';
                    foreach ($journals as $journal) {
                        $sum = bcadd($sum, TransactionJournal::amountPositive($journal));
                    }
                    $average = bcdiv($sum, strval($count));
                }

                $bill->lastPaidAmount = $average;
                $bill->paidInPeriod   = ($start <= $bill->lastFoundMatch) && ($end >= $bill->lastFoundMatch);

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
     * @return \Illuminate\View\View
     */
    public function show(BillRepositoryInterface $repository, Bill $bill)
    {
        $page                    = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $pageSize                = Preferences::get('transactionPageSize', 50)->data;
        $journals                = $repository->getJournals($bill, $page, $pageSize);
        $journals->setPath('/bills/show/' . $bill->id);
        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
        $hideBill                = true;
        $subTitle                = e($bill->name);

        return view('bills.show', compact('journals', 'hideBill', 'bill', 'subTitle'));
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
