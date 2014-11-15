<?php

use FireflyIII\Exception\FireflyException;
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
     * Add money to piggy bank
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
     * @return mixed
     */
    public function create()
    {

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Shared\Toolkit\Form $toolkit */
        $toolkit = App::make('FireflyIII\Shared\Toolkit\Form');

        $periods = Config::get('firefly.piggybank_periods');


        $accounts = $toolkit->makeSelectList($acct->getAssetAccounts());

        return View::make('piggybanks.create', compact('accounts', 'periods'))->with('title', 'Piggy banks')->with('mainTitleIcon', 'fa-sort-amount-asc')->with(
            'subTitle', 'Create new piggy bank'
        )->with('subTitleIcon', 'fa-plus');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function delete(Piggybank $piggybank)
    {
        return View::make('piggybanks.delete')->with('piggybank', $piggybank)->with('subTitle', 'Delete "' . $piggybank->name . '"')->with(
            'title', 'Piggy banks'
        )->with('mainTitleIcon', 'fa-sort-amount-asc');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Piggybank $piggyBank)
    {
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
        $prefilled = ['name'       => $piggybank->name, 'account_id' => $piggybank->account_id, 'targetamount' => $piggybank->targetamount,
                      'targetdate' => $piggybank->targetdate, 'remind_me' => intval($piggybank->remind_me) == 1 ? true : false];
        Session::flash('prefilled', $prefilled);

        return View::make('piggybanks.edit', compact('piggybank', 'accounts', 'periods', 'prefilled'))->with('title', 'Piggybanks')->with(
            'mainTitleIcon', 'fa-sort-amount-asc'
        )->with('subTitle', 'Edit piggy bank "' . e($piggybank->name) . '"')->with('subTitleIcon', 'fa-pencil');
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
                $accounts[$account->id] = ['name'              => $account->name, 'balance' => $account->balance(),
                                           'leftForPiggybanks' => $repos->leftOnAccount($account), 'sumOfSaved' => $piggybank->savedSoFar,
                                           'sumOfTargets'      => floatval($piggybank->targetamount), 'leftToSave' => $piggybank->leftToSave];
            } else {
                $accounts[$account->id]['sumOfSaved'] += $piggybank->savedSoFar;
                $accounts[$account->id]['sumOfTargets'] += floatval($piggybank->targetamount);
                $accounts[$account->id]['leftToSave'] += $piggybank->leftToSave;
            }
        }

        return View::make('piggybanks.index', compact('piggybanks', 'accounts'))->with('title', 'Piggy banks')->with('mainTitleIcon', 'fa-sort-amount-asc');
    }

    /**
     * POST add money to piggy bank
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

            /*
             * Create event!
             */
            Event::fire('piggybank.addMoney',[$piggybank, $amount]);

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
            Event::fire('piggybank.removeMoney',[$piggybank, $amount]);

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
        return View::make('piggybanks.remove', compact('piggybank'));
    }

    public function show(Piggybank $piggybank)
    {

        return View::make('piggybanks.show', compact('piggybank'))->with('title', 'Piggy banks')->with('mainTitleIcon', 'fa-sort-amount-asc')->with(
            'subTitle', $piggybank->name
        );

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
                    return Redirect::route('piggybanks.create')->withInput();
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
    }

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