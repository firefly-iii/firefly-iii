<?php

use Carbon\Carbon;
use FireflyIII\Database\PiggyBank\PiggyBank as Repository;
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\Collection;

/**
 *
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 * @SuppressWarnings("TooManyMethods") // I'm also fine with this.
 * @SuppressWarnings("CouplingBetweenObjects") // There's only so much I can remove.
 *
 *
 * Class PiggybankController
 *
 */
class PiggybankController extends BaseController
{

    /** @var Repository */
    protected $_repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->_repository = $repository;
        View::share('title', 'Piggy banks');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');
    }

    /**
     * Add money to piggy bank
     *
     * @param Piggybank $piggybank
     *
     * @return $this
     */
    public function add(Piggybank $piggybank)
    {
        \Log::debug('Now in add() for piggy bank #' . $piggybank->id . ' (' . $piggybank->name . ')');
        \Log::debug('Z');
        \Log::debug('currentRelevantRep is null: ' . boolstr($piggybank->currentRelevantRep()));
        $leftOnAccount = $this->_repository->leftOnAccount($piggybank->account);
        \Log::debug('A');

        $savedSoFar = $piggybank->currentRelevantRep()->currentamount;
        \Log::debug('B');
        $leftToSave = $piggybank->targetamount - $savedSoFar;
        \Log::debug('C');
        $maxAmount = min($leftOnAccount, $leftToSave);
        \Log::debug('D');


        \Log::debug('Now going to view for piggy bank #' . $piggybank->id . ' (' . $piggybank->name . ')');

        return View::make('piggybanks.add', compact('piggybank', 'maxAmount'));
    }

    /**
     * @return mixed
     */
    public function create()
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggybank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAssetAccounts());
        $subTitle     = 'Create new piggy bank';
        $subTitleIcon = 'fa-plus';

        return View::make('piggybanks.create', compact('accounts', 'periods', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return $this
     */
    public function delete(Piggybank $piggybank)
    {
        $subTitle = 'Delete "' . e($piggybank->name) . '"';

        return View::make('piggybanks.delete', compact('piggybank', 'subTitle'));
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Piggybank $piggyBank)
    {

        Session::flash('success', 'Piggy bank "' . e($piggyBank->name) . '" deleted.');
        $this->_repository->destroy($piggyBank);

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return $this
     */
    public function edit(Piggybank $piggybank)
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggybank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAssetAccounts());
        $subTitle     = 'Edit piggy bank "' . e($piggybank->name) . '"';
        $subTitleIcon = 'fa-pencil';

        /*
         * Flash some data to fill the form.
         */
        if (is_null($piggybank->targetdate) || $piggybank->targetdate == '') {
            $targetDate = null;
        } else {
            $targetDate = new Carbon($piggybank->targetdate);
            $targetDate = $targetDate->format('Y-m-d');
        }
        $preFilled = ['name'         => $piggybank->name,
                      'account_id'   => $piggybank->account_id,
                      'targetamount' => $piggybank->targetamount,
                      'targetdate'   => $targetDate,
                      'reminder'     => $piggybank->reminder,
                      'remind_me'    => intval($piggybank->remind_me) == 1 || !is_null($piggybank->reminder) ? true : false
        ];
        Session::flash('preFilled', $preFilled);

        return View::make('piggybanks.edit', compact('subTitle', 'subTitleIcon', 'piggybank', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @return $this
     */
    public function index()
    {
        /** @var Collection $piggybanks */
        $piggybanks = $this->_repository->get();

        $accounts = [];
        /** @var Piggybank $piggybank */
        foreach ($piggybanks as $piggybank) {
            $piggybank->savedSoFar = floatval($piggybank->currentRelevantRep()->currentamount);
            $piggybank->percentage = intval($piggybank->savedSoFar / $piggybank->targetamount * 100);
            $piggybank->leftToSave = $piggybank->targetamount - $piggybank->savedSoFar;

            /*
             * Fill account information:
             */
            $account = $piggybank->account;
            if (!isset($accounts[$account->id])) {
                $accounts[$account->id] = [
                    'name'              => $account->name,
                    'balance'           => Steam::balance($account),
                    'leftForPiggybanks' => $this->_repository->leftOnAccount($account),
                    'sumOfSaved'        => $piggybank->savedSoFar,
                    'sumOfTargets'      => floatval($piggybank->targetamount),
                    'leftToSave'        => $piggybank->leftToSave
                ];
            } else {
                $accounts[$account->id]['sumOfSaved'] += $piggybank->savedSoFar;
                $accounts[$account->id]['sumOfTargets'] += floatval($piggybank->targetamount);
                $accounts[$account->id]['leftToSave'] += $piggybank->leftToSave;
            }
        }

        return View::make('piggybanks.index', compact('piggybanks', 'accounts'));
    }

    /**
     * POST add money to piggy bank
     *
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(Piggybank $piggybank)
    {
        $amount = round(floatval(Input::get('amount')), 2);

        /** @var \FireflyIII\Database\PiggyBank\PiggyBank $acct */
        $repos = App::make('FireflyIII\Database\PiggyBank\PiggyBank');

        $leftOnAccount = $repos->leftOnAccount($piggybank->account);
        $savedSoFar    = $piggybank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggybank->targetamount - $savedSoFar;
        $maxAmount     = round(min($leftOnAccount, $leftToSave), 2);

        if ($amount <= $maxAmount) {
            $repetition = $piggybank->currentRelevantRep();
            $repetition->currentamount += $amount;
            $repetition->save();

            /*
             * Create event!
             */
            Event::fire('piggybank.addMoney', [$piggybank, $amount]); // new and used.

            Session::flash('success', 'Added ' . mf($amount, false) . ' to "' . e($piggybank->name) . '".');
        } else {
            Session::flash('error', 'Could not add ' . mf($amount, false) . ' to "' . e($piggybank->name) . '".');
        }

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(Piggybank $piggybank)
    {
        $amount = floatval(Input::get('amount'));

        $savedSoFar = $piggybank->currentRelevantRep()->currentamount;

        if ($amount <= $savedSoFar) {
            $repetition = $piggybank->currentRelevantRep();
            $repetition->currentamount -= $amount;
            $repetition->save();

            /*
             * Create event!
             */
            Event::fire('piggybank.removeMoney', [$piggybank, $amount]); // new and used.

            Session::flash('success', 'Removed ' . mf($amount, false) . ' from "' . e($piggybank->name) . '".');
        } else {
            Session::flash('error', 'Could not remove ' . mf($amount, false) . ' from "' . e($piggybank->name) . '".');
        }

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\View\View
     */
    public function remove(Piggybank $piggybank)
    {
        return View::make('piggybanks.remove')->with('piggybank', $piggybank);
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return $this
     */
    public function show(Piggybank $piggybank)
    {

        $events = $piggybank->piggybankevents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();

        /*
         * Number of reminders:
         */

        $amountPerReminder = $piggybank->amountPerReminder();
        $remindersCount    = $piggybank->countFutureReminders();
        $subTitle          = e($piggybank->name);

        return View::make('piggybanks.show', compact('amountPerReminder', 'remindersCount', 'piggybank', 'events', 'subTitle'));

    }

    /**
     *
     */
    public function store()
    {
        $data            = Input::all();
        $data['repeats'] = 0;
        $data['user_id'] = Auth::user()->id;


        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store piggy bank: ' . $messages['errors']->first());
        }


        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('piggybanks.create')->withInput();
        }

        // store:
        $piggyBank = $this->_repository->store($data);
        Event::fire('piggybank.store', [$piggyBank]); // new and used.
        Session::flash('success', 'Piggy bank "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('piggybanks.index');
        }

        return Redirect::route('piggybanks.create')->withInput();
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(Piggybank $piggyBank)
    {

        $data                  = Input::except('_token');
        $data['rep_every']     = 0;
        $data['reminder_skip'] = 0;
        $data['order']         = 0;
        $data['remind_me']     = isset($data['remind_me']) ? 1 : 0;
        $data['user_id']       = Auth::user()->id;

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update piggy bank: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('piggybanks.edit', $piggyBank->id)->withInput();
        }

        // update
        $this->_repository->update($piggyBank, $data);
        Session::flash('success', 'Piggy bank "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('piggybanks.index');
        }

        // go back to update screen.
        return Redirect::route('piggybanks.edit', $piggyBank->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }
}