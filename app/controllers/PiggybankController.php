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
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function add(Piggybank $piggyBank)
    {
        $leftOnAccount = $this->_repository->leftOnAccount($piggyBank->account);
        $savedSoFar = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave = $piggyBank->targetamount - $savedSoFar;
        $maxAmount = min($leftOnAccount, $leftToSave);


        \Log::debug('Now going to view for piggy bank #' . $piggyBank->id . ' (' . $piggyBank->name . ')');

        return View::make('piggybanks.add', compact('piggyBank', 'maxAmount'));
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
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function delete(Piggybank $piggyBank)
    {
        $subTitle = 'Delete "' . e($piggyBank->name) . '"';

        return View::make('piggybanks.delete', compact('piggyBank', 'subTitle'));
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
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function edit(Piggybank $piggyBank)
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggybank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAssetAccounts());
        $subTitle     = 'Edit piggy bank "' . e($piggyBank->name) . '"';
        $subTitleIcon = 'fa-pencil';

        /*
         * Flash some data to fill the form.
         */
        if (is_null($piggyBank->targetdate) || $piggyBank->targetdate == '') {
            $targetDate = null;
        } else {
            $targetDate = new Carbon($piggyBank->targetdate);
            $targetDate = $targetDate->format('Y-m-d');
        }
        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
                      'reminder'     => $piggyBank->reminder,
                      'remind_me'    => intval($piggyBank->remind_me) == 1 || !is_null($piggyBank->reminder) ? true : false
        ];
        Session::flash('preFilled', $preFilled);

        return View::make('piggybanks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @return $this
     */
    public function index()
    {
        /** @var Collection $piggyBanks */
        $piggyBanks = $this->_repository->get();

        $accounts = [];
        /** @var Piggybank $piggyBank */
        foreach ($piggyBanks as $piggyBank) {
            $piggyBank->savedSoFar = floatval($piggyBank->currentRelevantRep()->currentamount);
            $piggyBank->percentage = intval($piggyBank->savedSoFar / $piggyBank->targetamount * 100);
            $piggyBank->leftToSave = $piggyBank->targetamount - $piggyBank->savedSoFar;

            /*
             * Fill account information:
             */
            $account = $piggyBank->account;
            if (!isset($accounts[$account->id])) {
                $accounts[$account->id] = [
                    'name'              => $account->name,
                    'balance'           => Steam::balance($account),
                    'leftForPiggybanks' => $this->_repository->leftOnAccount($account),
                    'sumOfSaved'        => $piggyBank->savedSoFar,
                    'sumOfTargets'      => floatval($piggyBank->targetamount),
                    'leftToSave'        => $piggyBank->leftToSave
                ];
            } else {
                $accounts[$account->id]['sumOfSaved'] += $piggyBank->savedSoFar;
                $accounts[$account->id]['sumOfTargets'] += floatval($piggyBank->targetamount);
                $accounts[$account->id]['leftToSave'] += $piggyBank->leftToSave;
            }
        }

        return View::make('piggybanks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * POST add money to piggy bank
     *
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(Piggybank $piggyBank)
    {
        $amount = round(floatval(Input::get('amount')), 2);

        /** @var \FireflyIII\Database\PiggyBank\PiggyBank $acct */
        $repos = App::make('FireflyIII\Database\PiggyBank\PiggyBank');

        $leftOnAccount = $repos->leftOnAccount($piggyBank->account);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggyBank->targetamount - $savedSoFar;
        $maxAmount     = round(min($leftOnAccount, $leftToSave), 2);

        if ($amount <= $maxAmount) {
            $repetition = $piggyBank->currentRelevantRep();
            $repetition->currentamount += $amount;
            $repetition->save();

            /*
             * Create event!
             */
            Event::fire('piggybank.addMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Added ' . mf($amount, false) . ' to "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not add ' . mf($amount, false) . ' to "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(Piggybank $piggyBank)
    {
        $amount = floatval(Input::get('amount'));

        $savedSoFar = $piggyBank->currentRelevantRep()->currentamount;

        if ($amount <= $savedSoFar) {
            $repetition = $piggyBank->currentRelevantRep();
            $repetition->currentamount -= $amount;
            $repetition->save();

            /*
             * Create event!
             */
            Event::fire('piggybank.removeMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Removed ' . mf($amount, false) . ' from "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not remove ' . mf($amount, false) . ' from "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\View\View
     */
    public function remove(Piggybank $piggyBank)
    {
        return View::make('piggybanks.remove',compact('piggyBank'));
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function show(Piggybank $piggyBank)
    {

        $events = $piggyBank->piggybankevents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();

        /*
         * Number of reminders:
         */

        $amountPerReminder = $piggyBank->amountPerReminder();
        $remindersCount    = $piggyBank->countFutureReminders();
        $subTitle          = e($piggyBank->name);

        return View::make('piggybanks.show', compact('amountPerReminder', 'remindersCount', 'piggyBank', 'events', 'subTitle'));

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