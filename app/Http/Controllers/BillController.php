<?php namespace FireflyIII\Http\Controllers;

use Config;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\BillFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
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
     *
     */
    public function __construct()
    {
        View::share('title', 'Bills');
        View::share('mainTitleIcon', 'fa-calendar-o');
    }

    /**
     * @param Bill $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function add(Bill $bill, AccountRepositoryInterface $repository)
    {
        $matches     = explode(',', $bill->match);
        $description = [];
        $expense     = null;

        // get users expense accounts:
        $accounts = $repository->getAccounts(Config::get('firefly.accountTypesByIdentifier.expense'), -1);

        foreach ($matches as $match) {
            $match = strtolower($match);
            // find expense account for each word if not found already:
            if (is_null($expense)) {
                /** @var Account $account */
                foreach ($accounts as $account) {
                    $name = strtolower($account->name);
                    if (!(strpos($name, $match) === false)) {
                        $expense = $account;
                        break;
                    }
                }


            }
            if (is_null($expense)) {
                $description[] = $match;
            }
        }
        $parameters = [
            'description'     => ucfirst(join(' ', $description)),
            'expense_account' => is_null($expense) ? '' : $expense->name,
            'amount'          => round(($bill->amount_min + $bill->amount_max), 2),
        ];
        Session::put('preFilled', $parameters);

        return Redirect::to(route('transactions.create', 'withdrawal'));
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = Config::get('firefly.periods_to_text');

        return view('bills.create')->with('periods', $periods)->with('subTitle', 'Create new');
    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function delete(Bill $bill)
    {
        return view('bills.delete')->with('bill', $bill)->with('subTitle', 'Delete "' . e($bill->name) . '"');
    }

    /**
     * @param Bill $bill
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Bill $bill, BillRepositoryInterface $repository)
    {
        $repository->destroy($bill);

        Session::flash('success', 'The bill was deleted.');

        return Redirect::route('bills.index');

    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function edit(Bill $bill)
    {
        $periods = Config::get('firefly.periods_to_text');

        return view('bills.edit')->with('periods', $periods)->with('bill', $bill)->with('subTitle', 'Edit "' . e($bill->name) . '"');
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
     * @param Bill $bill
     *
     * @return mixed
     */
    public function rescan(Bill $bill, BillRepositoryInterface $repository)
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
     * @param Bill $bill
     *
     * @return mixed
     */
    public function show(Bill $bill, BillRepositoryInterface $repository)
    {
        $journals                = $repository->getJournals($bill);
        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
        $hideBill                = true;

        return view('bills.show', compact('journals', 'hideBill', 'bill'))->with('subTitle', e($bill->name));
    }

    /**
     * @return $this
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->store($billData);
        Session::flash('success', 'Bill "' . e($bill->name) . '" stored.');

        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('bills.create')->withInput();
        }

        return Redirect::route('bills.index');

    }

    /**
     * @param Bill $bill
     *
     * @return $this
     */
    public function update(Bill $bill, BillFormRequest $request, BillRepositoryInterface $repository)
    {
        $billData = $request->getBillData();
        $bill     = $repository->update($bill, $billData);

        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('bills.edit', $bill->id);
        }

        Session::flash('success', 'Bill "' . e($bill->name) . '" updated.');

        return Redirect::route('bills.index');

    }

}
