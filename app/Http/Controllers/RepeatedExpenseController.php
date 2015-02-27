<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use ExpandedForm;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Redirect;
use Session;
use View;

/**
 * Class RepeatedExpenseController
 *
 * @package FireflyIII\Http\Controllers
 */
class RepeatedExpenseController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Repeated expenses');
        View::share('mainTitleIcon', 'fa-rotate-left');
    }

    /**
     * @return $this
     */
    public function create()
    {
        $periods  = Config::get('firefly.piggy_bank_periods');
        $accounts = ExpandedForm::makeSelectList(Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']));

        return view('repeatedExpense.create', compact('accounts', 'periods'))->with('subTitle', 'Create new repeated expense')->with(
            'subTitleIcon', 'fa-plus'
        );
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return $this
     */
    public function delete(PiggyBank $repeatedExpense)
    {
        $subTitle = 'Delete "' . e($repeatedExpense->name) . '"';

        return view('repeatedExpense.delete', compact('repeatedExpense', 'subTitle'));
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBank $repeatedExpense)
    {

        Session::flash('success', 'Repeated expense "' . e($repeatedExpense->name) . '" deleted.');

        $repeatedExpense->delete();

        return Redirect::route('repeated.index');
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return $this
     */
    public function edit(PiggyBank $repeatedExpense)
    {

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = ExpandedForm::makeSelectList(Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']));
        $subTitle     = 'Edit repeated expense "' . e($repeatedExpense->name) . '"';
        $subTitleIcon = 'fa-pencil';

        /*
         * Flash some data to fill the form.
         */
        $preFilled = ['name'          => $repeatedExpense->name,
                      'account_id'    => $repeatedExpense->account_id,
                      'targetamount'  => $repeatedExpense->targetamount,
                      'reminder_skip' => $repeatedExpense->reminder_skip,
                      'rep_every'     => $repeatedExpense->rep_every,
                      'rep_times'     => $repeatedExpense->rep_times,
                      'targetdate'    => $repeatedExpense->targetdate->format('Y-m-d'),
                      'reminder'      => $repeatedExpense->reminder,
                      'remind_me'     => intval($repeatedExpense->remind_me) == 1 || !is_null($repeatedExpense->reminder) ? true : false
        ];
        Session::flash('preFilled', $preFilled);

        return view('repeatedExpense.edit', compact('subTitle', 'subTitleIcon', 'repeatedExpense', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $subTitle = 'Overview';

        $expenses = Auth::user()->piggyBanks()->where('repeats', 1)->get();
        $expenses->each(
            function (PiggyBank $piggyBank) {
                $piggyBank->currentRelevantRep();
            }
        );

        return view('repeatedExpense.index', compact('expenses', 'subTitle'));
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return \Illuminate\View\View
     */
    public function show(PiggyBank $repeatedExpense, PiggyBankRepositoryInterface $repository)
    {
        $subTitle    = $repeatedExpense->name;
        $today       = Carbon::now();
        $repetitions = $repeatedExpense->piggyBankRepetitions()->get();

        $repetitions->each(
            function (PiggyBankRepetition $repetition) use ($repository) {
                $repetition->bars = $repository->calculateParts($repetition);
            }
        );

        return view('repeatedExpense.show', compact('repetitions', 'repeatedExpense', 'today', 'subTitle'));
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     */
    public function store(PiggyBankFormRequest $request, PiggyBankRepositoryInterface $repository)
    {

        $piggyBankData = [
            'repeats'      => true,
            'name'         => $request->get('name'),
            'startdate'    => new Carbon,
            'account_id'   => intval($request->get('account_id')),
            'targetamount' => floatval($request->get('targetamount')),
            'targetdate'   => new Carbon($request->get('targetdate')),
            'reminder'     => $request->get('reminder'),
            'skip'         => intval($request->get('skip')),
            'rep_every'    => intval($request->get('rep_every')),
            'rep_times'    => intval($request->get('rep_times')),
        ];

        $piggyBank = $repository->store($piggyBankData);

        Session::flash('success', 'Stored repeated expense "' . e($piggyBank->name) . '".');

        return Redirect::route('repeated.index');
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param PiggyBank $repeatedExpense
     *
     * @return $this
     */
    public function update(PiggyBank $repeatedExpense, PiggyBankFormRequest $request, PiggyBankRepositoryInterface $repository)
    {
        $piggyBankData = [
            'repeats'      => false,
            'name'         => $request->get('name'),
            'account_id'   => intval($request->get('account_id')),
            'targetamount' => floatval($request->get('targetamount')),
            'targetdate'   => strlen($request->get('targetdate')) > 0 ? new Carbon($request->get('targetdate')) : null,
            'rep_length'   => $request->get('rep_length'),
            'rep_every'    => intval($request->get('rep_every')),
            'rep_times'    => intval($request->get('rep_times')),
            'remind_me'    => intval($request->get('remind_me')) == 1 ? true : false ,
            'reminder'     => $request->get('reminder'),
        ];


        $piggyBank = $repository->update($repeatedExpense, $piggyBankData);

        Session::flash('success', 'Updated repeated expense "' . e($piggyBank->name) . '".');

        return Redirect::route('repeated.index');

    }

}
