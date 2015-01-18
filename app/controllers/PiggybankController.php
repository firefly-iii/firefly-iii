<?php

use Carbon\Carbon;
use FireflyIII\Database\PiggyBank\PiggyBank as Repository;
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\Collection;

/**
 *
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 *
 *
 * Class PiggyBankController
 *
 */
class PiggyBankController extends BaseController
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
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function add(PiggyBank $piggyBank)
    {
        $leftOnAccount = $this->_repository->leftOnAccount($piggyBank->account);
        $savedSoFar    = $piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggyBank->targetamount - $savedSoFar;
        $maxAmount     = min($leftOnAccount, $leftToSave);


        \Log::debug('Now going to view for piggy bank #' . $piggyBank->id . ' (' . $piggyBank->name . ')');

        return View::make('piggy_banks.add', compact('piggyBank', 'maxAmount'));
    }

    /**
     * @return mixed
     */
    public function create()
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAccountsByType(['Default account', 'Asset account']));
        $subTitle     = 'Create new piggy bank';
        $subTitleIcon = 'fa-plus';

        return View::make('piggy_banks.create', compact('accounts', 'periods', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = 'Delete "' . e($piggyBank->name) . '"';

        return View::make('piggy_banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBank $piggyBank)
    {

        Session::flash('success', 'Piggy bank "' . e($piggyBank->name) . '" deleted.');
        $this->_repository->destroy($piggyBank);

        return Redirect::route('piggy_banks.index');
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function edit(PiggyBank $piggyBank)
    {

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        $periods      = Config::get('firefly.piggy_bank_periods');
        $accounts     = FFForm::makeSelectList($acct->getAccountsByType(['Default account', 'Asset account']));
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

        return View::make('piggy_banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'accounts', 'periods', 'preFilled'));
    }

    /**
     * @return $this
     */
    public function index()
    {
        /** @var Collection $piggyBanks */
        $piggyBanks = $this->_repository->get();

        $accounts = [];
        /** @var PiggyBank $piggyBank */
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
                    'leftForPiggyBanks' => $this->_repository->leftOnAccount($account),
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

        return View::make('piggy_banks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * POST add money to piggy bank
     *
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(PiggyBank $piggyBank)
    {
        $amount = round(floatval(Input::get('amount')), 2);

        /** @var \FireflyIII\Database\PiggyBank\PiggyBank $acct */
        $piggyRepository = App::make('FireflyIII\Database\PiggyBank\PiggyBank');

        $leftOnAccount = $piggyRepository->leftOnAccount($piggyBank->account);
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
            Event::fire('piggy_bank.addMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Added ' . Amount::format($amount, false) . ' to "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not add ' . Amount::format($amount, false) . ' to "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggy_banks.index');
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(PiggyBank $piggyBank)
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
            Event::fire('piggy_bank.removeMoney', [$piggyBank, $amount]); // new and used.

            Session::flash('success', 'Removed ' . Amount::format($amount, false) . ' from "' . e($piggyBank->name) . '".');
        } else {
            Session::flash('error', 'Could not remove ' . Amount::format($amount, false) . ' from "' . e($piggyBank->name) . '".');
        }

        return Redirect::route('piggy_banks.index');
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @SuppressWarnings("Unused")
     *
     * @return \Illuminate\View\View
     */
    public function remove(PiggyBank $piggyBank)
    {
        return View::make('piggy_banks.remove', compact('piggyBank'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     */
    public function show(PiggyBank $piggyBank)
    {

        $events = $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();

        /*
         * Number of reminders:
         */

        $subTitle = e($piggyBank->name);

        return View::make('piggy_banks.show', compact('piggyBank', 'events', 'subTitle'));

    }

    /**
     *
     */
    public function store()
    {
        $data                  = Input::all();
        $data['repeats']       = 0;
        $data['user_id']       = Auth::user()->id;
        $data['rep_every']     = 0;
        $data['reminder_skip'] = 0;
        $data['remind_me']     = intval(Input::get('remind_me'));
        $data['order']         = 0;


        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store piggy bank: ' . $messages['errors']->first());
            return Redirect::route('piggy_banks.create')->withInput();
        }


        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only') {
            return Redirect::route('piggy_banks.create')->withInput();
        }

        // store
        $piggyBank = $this->_repository->store($data);
        Event::fire('piggy_bank.store', [$piggyBank]); // new and used.
        Session::flash('success', 'Piggy bank "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('piggy_banks.index');
        }

        return Redirect::route('piggy_banks.create')->withInput();
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(PiggyBank $piggyBank)
    {

        $data                  = Input::except('_token');
        $data['rep_every']     = 0;
        $data['reminder_skip'] = 0;
        $data['order']         = 0;
        $data['remind_me']     = isset($data['remind_me']) ? 1 : 0;
        $data['user_id']       = Auth::user()->id;
        $data['repeats']       = 0;

        // always validate:
        $messages = $this->_repository->validate($data);

        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update piggy bank: ' . $messages['errors']->first());
            return Redirect::route('piggy_banks.edit', $piggyBank->id)->withInput();
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only') {
            return Redirect::route('piggy_banks.edit', $piggyBank->id)->withInput();
        }

        // update
        $this->_repository->update($piggyBank, $data);
        Session::flash('success', 'Piggy bank "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('piggy_banks.index');
        }

        // go back to update screen.
        return Redirect::route('piggy_banks.edit', $piggyBank->id)->withInput(['post_submit_action' => 'return_to_edit']);

    }
}
