<?php

use Carbon\Carbon;
use FireflyIII\Database\PiggyBank\RepeatedExpense as Repository;
use FireflyIII\Exception\FireflyException;

/**
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 * @SuppressWarnings("CouplingBetweenObjects") // There's only so much I can remove.
 *
 * Class RepeatedExpenseController
 */
class RepeatedExpenseController extends BaseController
{
    /** @var  Repository */
    protected $_repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        View::share('title', 'Repeated expenses');
        View::share('mainTitleIcon', 'fa-rotate-left');
        $this->_repository = $repository;
    }

    /**
     * @return $this
     */
    public function create()
    {
        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct     = App::make('FireflyIII\Database\Account\Account');
        $periods  = Config::get('firefly.piggy_bank_periods');
        $accounts = FFForm::makeSelectList($acct->getAssetAccounts());

        return View::make('repeatedExpense.create', compact('accounts', 'periods'))->with('subTitle', 'Create new repeated expense')->with(
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

        return View::make('repeatedExpense.delete', compact('repeatedExpense', 'subTitle'));
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBank $repeatedExpense)
    {

        Session::flash('success', 'Repeated expense "' . e($repeatedExpense->name) . '" deleted.');
        $this->_repository->destroy($repeatedExpense);

        return Redirect::route('repeated.index');
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return $this
     */
    public function edit(PiggyBank $repeatedExpense)
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAssetAccounts());
        $subTitle     = 'Edit repeated expense "' . e($repeatedExpense->name) . '"';
        $subTitleIcon = 'fa-pencil';

        /*
         * Flash some data to fill the form.
         */
        $preFilled = ['name'         => $repeatedExpense->name,
                      'account_id'   => $repeatedExpense->account_id,
                      'targetamount' => $repeatedExpense->targetamount,
                      'targetdate'   => $repeatedExpense->targetdate->format('Y-m-d'),
                      'reminder'     => $repeatedExpense->reminder,
                      'remind_me'    => intval($repeatedExpense->remind_me) == 1 || !is_null($repeatedExpense->reminder) ? true : false
        ];
        Session::flash('preFilled', $preFilled);

        return View::make('repeatedExpense.edit', compact('subTitle', 'subTitleIcon', 'repeatedExpense', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $subTitle = 'Overview';

        $expenses = $this->_repository->get();
        $expenses->each(
            function (PiggyBank $piggyBank) {
                $piggyBank->currentRelevantRep();
            }
        );

        return View::make('repeatedExpense.index', compact('expenses', 'subTitle'));
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return \Illuminate\View\View
     */
    public function show(PiggyBank $repeatedExpense)
    {
        $subTitle    = $repeatedExpense->name;
        $today       = Carbon::now();
        $repetitions = $repeatedExpense->piggyBankRepetitions()->get();

        $repetitions->each(
            function (PiggyBankRepetition $repetition) {
                $repetition->bars = $this->_repository->calculateParts($repetition);
            }
        );

        return View::make('repeatedExpense.show', compact('repetitions', 'repeatedExpense', 'today', 'subTitle'));
    }

    /**
     *
     */
    public function store()
    {
        $data                  = Input::all();
        $data['repeats']       = 1;
        $data['user_id']       = Auth::user()->id;
        $targetDate            = new Carbon($data['targetdate']);
        $startDate             = \DateKit::subtractPeriod($targetDate, $data['rep_length']);
        $data['startdate']     = $startDate->format('Y-m-d');
        $data['targetdate']    = $targetDate->format('Y-m-d');
        $data['reminder_skip'] = 0;
        $data['remind_me']     = isset($data['remind_me']) ? 1 : 0;
        $data['order']         = 0;

        // always validate:
        $messages = $this->_repository->validate($data);

        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store repeated expense: ' . $messages['errors']->first());
        }


        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('repeated.create')->withInput();
        }

        // store
        $piggyBank = $this->_repository->store($data);
        Event::fire('piggy_bank.store', [$piggyBank]); // new and used.
        Session::flash('success', 'Piggy bank "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('repeated.index');
        }

        return Redirect::route('repeated.create')->withInput();
    }

    /**
     * @param PiggyBank $repeatedExpense
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(PiggyBank $repeatedExpense)
    {

        $data                  = Input::except('_token');
        $data['rep_every']     = 0;
        $data['reminder_skip'] = 0;
        $data['order']         = 0;
        $data['repeats']       = 1;
        $data['remind_me']     = isset($data['remind_me']) ? 1 : 0;
        $data['user_id']       = Auth::user()->id;

        // always validate:
        $messages = $this->_repository->validate($data);

        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update repeated expense: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('repeated.edit', $repeatedExpense->id)->withInput();
        }

        // update
        $this->_repository->update($repeatedExpense, $data);
        Session::flash('success', 'Repeated expense "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('repeated.index');
        }

        // go back to update screen.
        return Redirect::route('repeated.edit', $repeatedExpense->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }
} 
