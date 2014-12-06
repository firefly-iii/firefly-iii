<?php

use Carbon\Carbon;
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\MessageBag;

class RepeatedExpenseController extends BaseController
{
    public function __construct()
    {
        View::share('title', 'Repeated expenses');
        View::share('mainTitleIcon', 'fa-rotate-left');
    }

    public function create()
    {
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        $periods = Config::get('firefly.piggybank_periods');


        $accounts = FFForm::makeSelectList($acct->getAssetAccounts());

        return View::make('repeatedexpense.create', compact('accounts', 'periods'))->with('subTitle', 'Create new repeated expense')->with(
            'subTitleIcon', 'fa-plus'
        );
    }

    public function index()
    {

        $subTitle = 'Overview';

        /** @var \FireflyIII\Database\RepeatedExpense $repository */
        $repository = App::make('FireflyIII\Database\RepeatedExpense');

        $expenses = $repository->get();
        $expenses->each(
            function (Piggybank $piggyBank) use ($repository) {
                $piggyBank->currentRelevantRep();
            }
        );

        return View::make('repeatedexpense.index', compact('expenses', 'subTitle'));
    }

    public function show(Piggybank $piggyBank)
    {
        $subTitle = $piggyBank->name;
        $today    = Carbon::now();

        /** @var \FireflyIII\Database\RepeatedExpense $repository */
        $repository = App::make('FireflyIII\Database\RepeatedExpense');

        $repetitions = $piggyBank->piggybankrepetitions()->get();
        $repetitions->each(
            function (PiggybankRepetition $repetition) use ($repository) {
                $repetition = $repository->calculateParts($repetition);
            }
        );

        return View::make('repeatedexpense.show', compact('repetitions', 'piggyBank', 'today', 'subTitle'));
    }

    /**
     *
     */
    public function store()
    {
        $data            = Input::all();
        $data['repeats'] = 1;
        /** @var \FireflyIII\Database\RepeatedExpense $repository */
        $repository = App::make('FireflyIII\Database\RepeatedExpense');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'create_another':
            case 'store':
                $messages = $repository->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save repeated expense: ' . $messages['errors']->first());

                    return Redirect::route('repeated.create')->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repeated = $repository->store($data);

                /*
                 * Create the relevant repetition per Event.
                 */
                Event::fire('piggybank.store', [$repeated]); // new and used.

                Session::flash('success', 'New repeated expense stored!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('repeated.create')->withInput();
                } else {
                    return Redirect::route('repeated.index');
                }
                break;
            case 'validate_only':
                $messageBags = $repository->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('repeated.create')->withInput();
                break;
        }
    }
} 