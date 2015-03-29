<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\BillFormRequest;
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

    public function __construct()
    {
        View::share('title', 'Bills');
        View::share('mainTitleIcon', 'fa-calendar-o');
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods = \Config::get('firefly.periods_to_text');

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
    public function destroy(Bill $bill)
    {
        $bill->delete();
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
        $periods = \Config::get('firefly.periods_to_text');

        return view('bills.edit')->with('periods', $periods)->with('bill', $bill)->with('subTitle', 'Edit "' . e($bill->name) . '"');
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\View\View
     */
    public function index(BillRepositoryInterface $repository)
    {
        $bills = Auth::user()->bills()->orderBy('name', 'ASC')->get();
        $bills->each(
            function (Bill $bill) use ($repository) {
                $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
                $last                    = $bill->transactionjournals()->orderBy('date', 'DESC')->first();
                $bill->lastFoundMatch    = null;
                if ($last) {
                    $bill->lastFoundMatch = $last->date;
                }
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

            return Redirect::intended('/');
        }

        $set = \DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $bill->amount_min)->where('amount', '<=', $bill->amount_max)->get(
            ['transaction_journal_id']
        );
        $ids = [];

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            $ids[] = intval($entry->transaction_journal_id);
        }
        if (count($ids) > 0) {
            $journals = Auth::user()->transactionjournals()->whereIn('id', $ids)->get();
            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                $repository->scan($bill, $journal);
            }
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
        $journals                = $bill->transactionjournals()->withRelevantData()
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order','ASC')
            ->orderBy('transaction_journals.id','DESC')

            ->get();
        $bill->nextExpectedMatch = $repository->nextExpectedMatch($bill);
        $hideBill                = true;


        return view('bills.show', compact('journals', 'hideBill', 'bill'))->with('subTitle', e($bill->name));
    }

    /**
     * @return $this
     */
    public function store(BillFormRequest $request, BillRepositoryInterface $repository)
    {

        $billData = [
            'name'               => $request->get('name'),
            'match'              => $request->get('match'),
            'amount_min'         => floatval($request->get('amount_min')),
            'amount_currency_id' => floatval($request->get('amount_currency_id')),
            'amount_max'         => floatval($request->get('amount_max')),
            'date'               => new Carbon($request->get('date')),
            'user'               => Auth::user()->id,
            'repeat_freq'        => $request->get('repeat_freq'),
            'skip'               => intval($request->get('skip')),
            'automatch'          => intval($request->get('automatch')) === 1,
            'active'             => intval($request->get('active')) === 1,
        ];

        $bill = $repository->store($billData);
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
        $billData = [
            'name'               => $request->get('name'),
            'match'              => $request->get('match'),
            'amount_min'         => floatval($request->get('amount_min')),
            'amount_currency_id' => floatval($request->get('amount_currency_id')),
            'amount_max'         => floatval($request->get('amount_max')),
            'date'               => new Carbon($request->get('date')),
            'user'               => Auth::user()->id,
            'repeat_freq'        => $request->get('repeat_freq'),
            'skip'               => intval($request->get('skip')),
            'automatch'          => intval($request->get('automatch')) === 1,
            'active'             => intval($request->get('active')) === 1,
        ];

        $bill = $repository->update($bill, $billData);

        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('bills.edit', $bill->id);
        }

        Session::flash('success', 'Bill "' . e($bill->name) . '" updated.');

        return Redirect::route('bills.index');

    }

}
