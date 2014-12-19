<?php

use Carbon\Carbon;
use FireflyIII\Database\PiggyBank\RepeatedExpense as Repository;
use FireflyIII\Exception\FireflyException;

/**
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
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods = Config::get('firefly.piggybank_periods');


        $accounts = FFForm::makeSelectList($acct->getAssetAccounts());

        return View::make('repeatedexpense.create', compact('accounts', 'periods'))->with('subTitle', 'Create new repeated expense')->with(
            'subTitleIcon', 'fa-plus'
        );
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $subTitle = 'Overview';

        /** @var \FireflyIII\Database\PiggyBank\RepeatedExpense $repository */
        $repository = App::make('FireflyIII\Database\PiggyBank\RepeatedExpense');

        $expenses = $repository->get();
        $expenses->each(
            function (Piggybank $piggyBank) use ($repository) {
                $piggyBank->currentRelevantRep();
            }
        );

        return View::make('repeatedexpense.index', compact('expenses', 'subTitle'));
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\View\View
     */
    public function show(Piggybank $piggyBank)
    {
        $subTitle = $piggyBank->name;
        $today    = Carbon::now();

        /** @var \FireflyIII\Database\PiggyBank\RepeatedExpense $repository */
        $repository = App::make('FireflyIII\Database\PiggyBank\RepeatedExpense');

        $repetitions = $piggyBank->piggybankrepetitions()->get();
        $repetitions->each(
            function (PiggybankRepetition $repetition) use ($repository) {
                $repetition->bars = $repository->calculateParts($repetition);
            }
        );

        return View::make('repeatedexpense.show', compact('repetitions', 'piggyBank', 'today', 'subTitle'));
    }

    /**
     * @return $this
     * @throws FireflyException
     */
    public function store()
    {
        $data            = Input::except('_token');
        $data['repeats'] = 1;

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not validate repeated expense: ' . $messages['errors']->first());
        }
        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('repeated.create')->withInput();
        }

        // store:
        $this->_repository->store($data);
        Session::flash('success', 'Budget "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('repeated.index');
        }

        // create another.
        if ($data['post_submit_action'] == 'create_another') {
            return Redirect::route('repeated.create')->withInput();
        }

        return Redirect::route('repeated.index');
    }
} 