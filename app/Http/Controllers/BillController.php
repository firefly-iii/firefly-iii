<?php namespace FireflyIII\Http\Controllers;

use Config;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Input;
use Redirect;
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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.bills'));
        View::share('mainTitleIcon', 'fa-calendar-o');
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = Config::get('firefly.periods_to_text');

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('bills.create.fromStore') !== true) {
            Session::put('bills.create.url', URL::previous());
        }
        Session::forget('bills.create.fromStore');
        $subTitle = 'Create new bill';

        return view('bills.create', compact('periods', 'subTitle'));
    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function delete(Bill $bill)
    {
        // put previous url in session
        Session::put('bills.delete.url', URL::previous());
        $subTitle = 'Delete "' . e($bill->name) . '"';

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
        $repository->destroy($bill);

        Session::flash('success', 'The bill was deleted.');

        return Redirect::to(Session::get('bills.delete.url'));

    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function edit(Bill $bill)
    {
        $periods  = Config::get('firefly.periods_to_text');
        $subTitle = 'Edit "' . e($bill->name) . '"';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('bills.edit.fromUpdate') !== true) {
            Session::put('bills.edit.url', URL::previous());
        }
        Session::forget('bills.edit.fromUpdate');

        return view('bills.edit', compact('subTitle', 'periods', 'bill'));
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\View\View
     */
    public function index(BillRepositoryInterface $repository)
    {
        $bills = $repository->getBills();
        $bills->each(
            function (Bill $bill) use ($repository) {
                $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
                $bill->lastFoundMatch    = $repository->lastFoundMatch($bill);
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
            Session::flash('warning', 'Inactive bills cannot be scanned.');

            return Redirect::to(URL::previous());
        }

        $journals = $repository->getPossiblyRelatedJournals($bill);
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $repository->scan($bill, $journal);
        }


        Session::flash('success', 'Rescanned everything.');

        return Redirect::to(URL::previous());
    }

    /**
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return mixed
     */
    public function show(BillRepositoryInterface $repository, Bill $bill)
    {
        $journals                = $repository->getJournals($bill);
        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
        $hideBill                = true;
        $subTitle                = e($bill->name);

        return view('bills.show', compact('journals', 'hideBill', 'bill', 'subTitle'));
    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->store($billData);
        Session::flash('success', 'Bill "' . e($bill->name) . '" stored.');

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('bills.create.fromStore', true);

            return Redirect::route('bills.create')->withInput();
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('bills.create.url'));

    }

    /**
     * @param BillFormRequest         $request
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(BillFormRequest $request, BillRepositoryInterface $repository, Bill $bill)
    {
        $billData = $request->getBillData();
        $bill     = $repository->update($bill, $billData);

        Session::flash('success', 'Bill "' . e($bill->name) . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('bills.edit.fromUpdate', true);

            return Redirect::route('bills.edit', $bill->id)->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('bills.edit.url'));

    }

}
