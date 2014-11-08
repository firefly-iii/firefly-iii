<?php

use Firefly\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class PiggybankController
 *
 */
class PiggybankController extends BaseController
{

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @throws NotImplementedException
     */
    public function create()
    {

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Shared\Toolkit\Form $toolkit */
        $toolkit = App::make('FireflyIII\Shared\Toolkit\Form');

        $periods = Config::get('firefly.piggybank_periods');


        $accounts = $toolkit->makeSelectList($acct->getAssetAccounts());
        return View::make('piggybanks.create', compact('accounts', 'periods'))->with('title', 'Piggy banks')->with('mainTitleIcon', 'fa-sort-amount-asc')
                   ->with('subTitle', 'Create new piggy bank')->with('subTitleIcon', 'fa-plus');
    }



//    /**
//     * @return $this
//     */
//    public function createRepeated()
//    {
//        throw new NotImplementedException;
//        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
//        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');
//
//        $periods  = Config::get('firefly.piggybank_periods');
//        $list     = $this->_accounts->getActiveDefault();
//        $accounts = $toolkit->makeSelectList($list);
//
//        View::share('title', 'Repeated expenses');
//        View::share('subTitle', 'Create new');
//        View::share('mainTitleIcon', 'fa-rotate-right');
//
//        return View::make('piggybanks.create-repeated')->with('accounts', $accounts)->with('periods', $periods);
//    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function delete(Piggybank $piggybank)
    {
        View::share('subTitle', 'Delete "' . $piggybank->name . '"');
        View::share('title', 'Piggy banks');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');

        return View::make('piggybanks.delete')->with('piggybank', $piggybank);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Piggybank $piggyBank)
    {
        Event::fire('piggybanks.destroy', [$piggyBank]);

        /** @var \FireflyIII\Database\Piggybank $acct */
        $repos = App::make('FireflyIII\Database\Piggybank');
        $repos->destroy($piggyBank);
        Session::flash('success', 'Piggy bank deleted.');

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function edit(Piggybank $piggybank)
    {

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Shared\Toolkit\Form $toolkit */
        $toolkit = App::make('FireflyIII\Shared\Toolkit\Form');

        $periods = Config::get('firefly.piggybank_periods');

        $accounts = $toolkit->makeSelectList($acct->getAssetAccounts());

        /*
         * Flash some data to fill the form.
         */
        $prefilled = [
            'name'         => $piggybank->name,
            'account_id'   => $piggybank->account_id,
            'targetamount' => $piggybank->targetamount,
            'targetdate'   => $piggybank->targetdate,
            'remind_me'    => intval($piggybank->remind_me) == 1 ? true : false
        ];
        Session::flash('prefilled', $prefilled);

        return View::make('piggybanks.edit', compact('piggybank', 'accounts', 'periods', 'prefilled'))->with('title', 'Piggybanks')->with(
            'mainTitleIcon', 'fa-sort-amount-asc'
        )
                   ->with('subTitle', 'Edit piggy bank "' . e($piggybank->name) . '"')->with('subTitleIcon', 'fa-pencil');
        //throw new NotImplementedException;
//        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
//        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');
//
//        $list     = $this->_accounts->getActiveDefault();
//        $accounts = $toolkit->makeSelectList($list);
//        $periods  = Config::get('firefly.piggybank_periods');
//
//
//        View::share('subTitle', 'Edit "' . $piggyBank->name . '"');
//
//
//        if ($piggyBank->repeats == 1) {
//            View::share('title', 'Repeated expenses');
//            View::share('mainTitleIcon', 'fa-rotate-left');
//
//            return View::make('piggybanks.edit-repeated')->with('piggybank', $piggyBank)->with('accounts', $accounts)
//                       ->with('periods', $periods);
//        } else {
//            // piggy bank.
//            View::share('title', 'Piggy banks');
//            View::share('mainTitleIcon', 'fa-sort-amount-asc');
//
//            return View::make('piggybanks.edit-piggybank')->with('piggybank', $piggyBank)->with('accounts', $accounts)
//                       ->with('periods', $periods);
//        }


    }

//    /**
//     * @param Piggybank $piggyBank
//     *
//     * @return \Illuminate\Http\RedirectResponse
//     * @throws Firefly\Exception\FireflyException
//     */
//    public function modMoney(Piggybank $piggyBank)
//    {
//        throw new NotImplementedException;
//        $amount = floatval(Input::get('amount'));
//        switch (Input::get('what')) {
//            default:
//                throw new FireflyException('No such action');
//                break;
//            case 'add':
//                $maxAdd = $this->_repository->leftOnAccount($piggyBank->account);
//                if (round($amount, 2) <= round(min($maxAdd, $piggyBank->targetamount), 2)) {
//                    Session::flash('success', 'Amount updated!');
//                    $this->_repository->modifyAmount($piggyBank, $amount);
//                    Event::fire('piggybanks.modifyAmountAdd', [$piggyBank, $amount]);
//                } else {
//                    Session::flash('warning', 'Could not!');
//                }
//                break;
//            case 'remove':
//                $rep       = $piggyBank->currentRelevantRep();
//                $maxRemove = $rep->currentamount;
//                if (round($amount, 2) <= round($maxRemove, 2)) {
//                    Session::flash('success', 'Amount updated!');
//                    $this->_repository->modifyAmount($piggyBank, ($amount * -1));
//                    Event::fire('piggybanks.modifyAmountRemove', [$piggyBank, ($amount * -1)]);
//                } else {
//                    Session::flash('warning', 'Could not!');
//                }
//                break;
//        }
//        if ($piggyBank->repeats == 1) {
//            $route = 'piggybanks.index.repeated';
//
//        } else {
//            $route = 'piggybanks.index.piggybanks';
//        }
//        return Redirect::route($route);
//    }


    /**
     * @param Piggybank $piggybank
     *
     * @return $this
     */
    public function add(Piggybank $piggybank)
    {
        /** @var \FireflyIII\Database\Piggybank $acct */
        $repos = App::make('FireflyIII\Database\Piggybank');

        $leftOnAccount = $repos->leftOnAccount($piggybank->account);
        $savedSoFar    = $piggybank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggybank->targetamount - $savedSoFar;
        $amount        = min($leftOnAccount, $leftToSave);


        return View::make('piggybanks.add', compact('piggybank'))->with('maxAmount', $amount);
    }

    /**
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(Piggybank $piggybank)
    {
        $amount = round(floatval(Input::get('amount')), 2);

        /** @var \FireflyIII\Database\Piggybank $acct */
        $repos = App::make('FireflyIII\Database\Piggybank');

        $leftOnAccount = $repos->leftOnAccount($piggybank->account);
        $savedSoFar    = $piggybank->currentRelevantRep()->currentamount;
        $leftToSave    = $piggybank->targetamount - $savedSoFar;
        $maxAmount     = round(min($leftOnAccount, $leftToSave), 2);

        if ($amount <= $maxAmount) {
            $repetition = $piggybank->currentRelevantRep();
            $repetition->currentamount += $amount;
            $repetition->save();
            Session::flash('success', 'Added ' . mf($amount, false) . ' to "' . e($piggybank->name) . '".');
        } else {
            Session::flash('error', 'Could not add ' . mf($amount, false) . ' to "' . e($piggybank->name) . '".');
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
        return View::make('piggybanks.remove', compact('piggybank'));
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
            Session::flash('success', 'Removed ' . mf($amount, false) . ' from "' . e($piggybank->name) . '".');
        } else {
            Session::flash('error', 'Could not remove ' . mf($amount, false) . ' from "' . e($piggybank->name) . '".');
        }
        return Redirect::route('piggybanks.index');
    }

    public function index()
    {
        /** @var \FireflyIII\Database\Piggybank $repos */
        $repos = App::make('FireflyIII\Database\Piggybank');

        /** @var Collection $piggybanks */
        $piggybanks = $repos->get();

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
                    'balance'           => $account->balance(),
                    'leftForPiggybanks' => $repos->leftOnAccount($account),
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
        return View::make('piggybanks.index', compact('piggybanks', 'accounts'))->with('title', 'Piggy banks')->with('mainTitleIcon', 'fa-sort-amount-asc');

        //throw new NotImplementedException;
//        $countRepeating    = $this->_repository->countRepeating();
//        $countNonRepeating = $this->_repository->countNonrepeating();
//
//        $piggybanks = $this->_repository->get();
//
//        // get the accounts with each piggy bank and check their balance; Fireflyy might needs to
//        // show the user a correction.
//
//        $accounts = [];
//        /** @var \Piggybank $piggybank */
//        foreach ($piggybanks as $piggybank) {
//            $account = $piggybank->account;
//            $id      = $account->id;
//            if (!isset($accounts[$id])) {
//                $account->leftOnAccount = $this->_repository->leftOnAccount($account);
//                $accounts[$id]          = [
//                    'account' => $account,
//                    'left'    => $this->_repository->leftOnAccount($account),
//                    'tosave'  => $piggybank->targetamount,
//                    'saved'   => $piggybank->currentRelevantRep()->currentamount
//                ];
//            } else {
//                $accounts[$id]['tosave'] += $piggybank->targetamount;
//                $accounts[$id]['saved'] += $piggybank->currentRelevantRep()->currentamount;
//            }
//        }
//
//        View::share('title', 'Piggy banks');
//        View::share('subTitle', 'Save for big expenses');
//        View::share('mainTitleIcon', 'fa-sort-amount-asc');
//
//        return View::make('piggybanks.index')->with('piggybanks', $piggybanks)
//                   ->with('countRepeating', $countRepeating)
//                   ->with('countNonRepeating', $countNonRepeating)
//                   ->with('accounts', $accounts);
    }

//    /**
//     * @param Piggybank $piggyBank
//     *
//     * @return $this
//     */
//    public function removeMoney(Piggybank $piggyBank)
//    {
//        $what      = 'remove';
//        $maxAdd    = $this->_repository->leftOnAccount($piggyBank->account);
//        $maxRemove = $piggyBank->currentRelevantRep()->currentamount;
//
//        return View::make('piggybanks.modifyAmount')->with('what', $what)->with('maxAdd', $maxAdd)->with(
//            'maxRemove', $maxRemove
//        )->with('piggybank', $piggyBank);
//    }

//    /**
//     * @return $this
//     */
//    public function repeated()
//    {
//        $countRepeating    = $this->_repository->countRepeating();
//        $countNonRepeating = $this->_repository->countNonrepeating();
//
//        $piggybanks = $this->_repository->get();
//
//        // get the accounts with each piggy bank and check their balance; Fireflyy might needs to
//        // show the user a correction.
//
//        $accounts = [];
//        /** @var \Piggybank $piggybank */
//        foreach ($piggybanks as $piggybank) {
//            $account = $piggybank->account;
//            $id      = $account->id;
//            if (!isset($accounts[$id])) {
//                $account->leftOnAccount = $this->_repository->leftOnAccount($account);
//                $accounts[$id]          = ['account' => $account, 'left' => $this->_repository->leftOnAccount($account)];
//            }
//        }
//
//        View::share('title', 'Repeated expenses');
//        View::share('subTitle', 'Save for returning bills');
//        View::share('mainTitleIcon', 'fa-rotate-left');
//
//
//        return View::make('piggybanks.index')->with('piggybanks', $piggybanks)
//                   ->with('countRepeating', $countRepeating)
//                   ->with('countNonRepeating', $countNonRepeating)
//                   ->with('accounts', $accounts);
//    }

//    /**
//     * @param Piggybank $piggyBank
//     *
//     * @return $this
//     * @throws NotImplementedException
//     */
    public function show(Piggybank $piggyBank)
    {
        throw new NotImplementedException;
//        $leftOnAccount = $this->_repository->leftOnAccount($piggyBank->account);
//        $balance       = $piggyBank->account->balance();
//
//        View::share('subTitle', $piggyBank->name);
//
//        if ($piggyBank->repeats == 1) {
//            // repeated expense.
//            View::share('title', 'Repeated expenses');
//            View::share('mainTitleIcon', 'fa-rotate-left');
//        } else {
//            // piggy bank.
//            View::share('title', 'Piggy banks');
//            View::share('mainTitleIcon', 'fa-sort-amount-asc');
//        }
//
//        return View::make('piggybanks.show')->with('piggyBank', $piggyBank)->with('leftOnAccount', $leftOnAccount)
//                   ->with('balance', $balance);
    }

    /**
     *
     */
    public function store()
    {
        $data            = Input::all();
        $data['repeats'] = 0;
        /** @var \FireflyIII\Database\Piggybank $repos */
        $repos = App::make('FireflyIII\Database\Piggybank');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'create_another':
            case 'store':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save piggy bank: ' . $messages['errors']->first());
                    return Redirect::route('piggybanks.create')->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->store($data);
                Session::flash('success', 'New piggy bank stored!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('piggybanks.create');
                } else {
                    return Redirect::route('piggybanks.index');
                }
                break;
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('piggybanks.create')->withInput();
                break;
        }
//        $data = Input::all();
//        unset($data['_token']);
//
//        // extend the data array with the settings needed to create a piggy bank:
//        $data['repeats']   = 0;
//        $data['rep_times'] = 1;
//        $data['rep_every'] = 1;
//        $data['order']     = 0;
//
//        $piggyBank = $this->_repository->store($data);
//        if (!is_null($piggyBank->id)) {
//            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');
//            Event::fire('piggybanks.store', [$piggyBank]);
//
//            return Redirect::route('piggybanks.index.piggybanks');
//
//
//        } else {
//            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());
//
//            return Redirect::route('piggybanks.create.piggybank')->withInput()->withErrors($piggyBank->errors());
//        }

    }

//    /**
//     * @return $this|\Illuminate\Http\RedirectResponse
//     */
//    public function storeRepeated()
//    {
//
//        $data = Input::all();
//        unset($data['_token']);
//
//        // extend the data array with the settings needed to create a repeated:
//        $data['repeats'] = 1;
//        $data['order']   = 0;
//
//        $piggyBank = $this->_repository->store($data);
//        if ($piggyBank->id) {
//            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');
//            Event::fire('piggybanks.store', [$piggyBank]);
//            return Redirect::route('piggybanks.index.repeated');
//
//        } else {
//            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());
//
//            return Redirect::route('piggybanks.create.repeated')->withInput()->withErrors($piggyBank->errors());
//        }
//
//    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Piggybank $piggyBank)
    {

        /** @var \FireflyIII\Database\Piggybank $repos */
        $repos = App::make('FireflyIII\Database\Piggybank');
        $data  = Input::except('_token');

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e(Input::get('post_submit_action')) . '"');
                break;
            case 'create_another':
            case 'update':
                $messages = $repos->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save piggy bank: ' . $messages['errors']->first());
                    return Redirect::route('piggybanks.edit', $piggyBank->id)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $repos->update($piggyBank, $data);
                Session::flash('success', 'Piggy bank updated!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('piggybanks.edit', $piggyBank->id);
                } else {
                    return Redirect::route('piggybanks.index');
                }
            case 'validate_only':
                $messageBags = $repos->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('piggybanks.edit', $piggyBank->id)->withInput();
                break;
        }


    }
}

//    /**
//     * @param Piggybank $piggyBank
//     *
//     * @return $this
//     */
//    public function addMoney(Piggybank $piggyBank)
//    {
//        throw new NotImplementedException;
//        $what      = 'add';
//        $maxAdd    = $this->_repository->leftOnAccount($piggyBank->account);
//        $maxRemove = null;
//
//        return View::make('piggybanks.modifyAmount')->with('what', $what)->with('maxAdd', $maxAdd)->with(
//            'maxRemove', $maxRemove
//        )->with('piggybank', $piggyBank);
//    }