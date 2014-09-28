<?php

use Firefly\Exception\FireflyException;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Piggybank\PiggybankRepositoryInterface as PRI;

/**
 * Class PiggybankController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 */
class PiggybankController extends BaseController
{

    protected $_accounts;
    protected $_repository;

    /**
     * @param PRI $repository
     * @param ARI $accounts
     */
    public function __construct(PRI $repository, ARI $accounts)
    {
        $this->_repository = $repository;
        $this->_accounts   = $accounts;
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function addMoney(Piggybank $piggyBank)
    {
        $what      = 'add';
        $maxAdd    = $this->_repository->leftOnAccount($piggyBank->account);
        $maxRemove = null;

        return View::make('piggybanks.modifyAmount')->with('what', $what)->with('maxAdd', $maxAdd)->with(
            'maxRemove', $maxRemove
        )->with('piggybank', $piggyBank);
    }

    /**
     * @return $this
     */
    public function createPiggybank()
    {
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');


        $periods  = Config::get('firefly.piggybank_periods');
        $list     = $this->_accounts->getActiveDefault();
        $accounts = $toolkit->makeSelectList($list);

        View::share('title', 'Piggy banks');
        View::share('subTitle', 'Create new');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');

        return View::make('piggybanks.create-piggybank')->with('accounts', $accounts)
            ->with('periods', $periods);
    }

    /**
     * @return $this
     */
    public function createRepeated()
    {
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');

        $periods  = Config::get('firefly.piggybank_periods');
        $list     = $this->_accounts->getActiveDefault();
        $accounts = $toolkit->makeSelectList($list);

        View::share('title', 'Repeated expenses');
        View::share('subTitle', 'Create new');
        View::share('mainTitleIcon', 'fa-rotate-right');

        return View::make('piggybanks.create-repeated')->with('accounts', $accounts)->with('periods', $periods);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function delete(Piggybank $piggyBank)
    {
        View::share('subTitle', 'Delete "' . $piggyBank->name . '"');
        if ($piggyBank->repeats == 1) {
            View::share('title', 'Repeated expenses');
            View::share('mainTitleIcon', 'fa-rotate-right')
        } else {
            View::share('title', 'Piggy banks');
            View::share('mainTitleIcon', 'fa-sort-amount-asc');
        }

        return View::make('piggybanks.delete')->with('piggybank', $piggyBank);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Piggybank $piggyBank)
    {
        Event::fire('piggybanks.destroy', [$piggyBank]);
        if ($piggyBank->repeats == 1) {
            $route   = 'piggybanks.index.repeated';
            $message = 'Repeated expense';
        } else {
            $route   = 'piggybanks.index.piggybanks';
            $message = 'Piggybank';
        }
        $this->_repository->destroy($piggyBank);

        Session::flash('success', $message . ' deleted.');

        return Redirect::route($route);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function edit(Piggybank $piggyBank)
    {
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');

        $list     = $this->_accounts->getActiveDefault();
        $accounts = $toolkit->makeSelectList($list);
        $periods  = Config::get('firefly.piggybank_periods');


        View::share('subTitle', 'Edit "' . $piggyBank->name . '"');


        if ($piggyBank->repeats == 1) {
            View::share('title', 'Repeated expenses');
            View::share('mainTitleIcon', 'fa-rotate-left');

            return View::make('piggybanks.edit-repeated')->with('piggybank', $piggyBank)->with('accounts', $accounts)
                ->with('periods', $periods);
        } else {
            // piggy bank.
            View::share('title', 'Piggy banks');
            View::share('mainTitleIcon', 'fa-sort-amount-asc');

            return View::make('piggybanks.edit-piggybank')->with('piggybank', $piggyBank)->with('accounts', $accounts)
                ->with('periods', $periods);
        }


    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws Firefly\Exception\FireflyException
     */
    public function modMoney(Piggybank $piggyBank)
    {
        $amount = floatval(Input::get('amount'));
        switch (Input::get('what')) {
            default:
                throw new FireflyException('No such action');
                break;
            case 'add':
                $maxAdd = $this->_repository->leftOnAccount($piggyBank->account);
                if (round($amount, 2) <= round(min($maxAdd, $piggyBank->targetamount), 2)) {
                    Session::flash('success', 'Amount updated!');
                    $this->_repository->modifyAmount($piggyBank, $amount);
                    Event::fire('piggybanks.modifyAmountAdd', [$piggyBank, $amount]);
                } else {
                    Session::flash('warning', 'Could not!');
                }
                break;
            case 'remove':
                $rep       = $piggyBank->currentRelevantRep();
                $maxRemove = $rep->currentamount;
                if (round($amount, 2) <= round($maxRemove, 2)) {
                    Session::flash('success', 'Amount updated!');
                    $this->_repository->modifyAmount($piggyBank, ($amount * -1));
                    Event::fire('piggybanks.modifyAmountRemove', [$piggyBank, ($amount * -1)]);
                } else {
                    Session::flash('warning', 'Could not!');
                }
                break;
        }

        return Redirect::route('piggybanks.index');
    }

    /**
     * @return $this
     */
    public function piggybanks()
    {
        $countRepeating    = $this->_repository->countRepeating();
        $countNonRepeating = $this->_repository->countNonrepeating();

        $piggybanks = $this->_repository->get();

        // get the accounts with each piggy bank and check their balance; Fireflyy might needs to
        // show the user a correction.

        $accounts = [];
        /** @var \Piggybank $piggybank */
        foreach ($piggybanks as $piggybank) {
            $account = $piggybank->account;
            $id      = $account->id;
            if (!isset($accounts[$id])) {
                $accounts[$id] = ['account' => $account, 'left' => $this->_repository->leftOnAccount($account)];
            }
        }

        View::share('title', 'Piggy banks');
        View::share('subTitle', 'Save for big expenses');
        View::share('mainTitleIcon', 'fa-sort-amount-asc');

        return View::make('piggybanks.index')->with('piggybanks', $piggybanks)
            ->with('countRepeating', $countRepeating)
            ->with('countNonRepeating', $countNonRepeating)
            ->with('accounts', $accounts);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function removeMoney(Piggybank $piggyBank)
    {
        $what      = 'remove';
        $maxAdd    = $this->_repository->leftOnAccount($piggyBank->account);
        $maxRemove = $piggyBank->currentRelevantRep()->currentamount;

        return View::make('piggybanks.modifyAmount')->with('what', $what)->with('maxAdd', $maxAdd)->with(
            'maxRemove', $maxRemove
        )->with('piggybank', $piggyBank);
    }

    /**
     * @return $this
     */
    public function repeated()
    {
        $countRepeating    = $this->_repository->countRepeating();
        $countNonRepeating = $this->_repository->countNonrepeating();

        $piggybanks = $this->_repository->get();

        // get the accounts with each piggy bank and check their balance; Fireflyy might needs to
        // show the user a correction.

        $accounts = [];
        /** @var \Piggybank $piggybank */
        foreach ($piggybanks as $piggybank) {
            $account = $piggybank->account;
            $id      = $account->id;
            if (!isset($accounts[$id])) {
                $accounts[$id] = ['account' => $account, 'left' => $this->_repository->leftOnAccount($account)];
            }
        }

        View::share('title', 'Repeated expenses');
        View::share('subTitle', 'Save for returning bills');
        View::share('mainTitleIcon', 'fa-rotate-left');


        return View::make('piggybanks.index')->with('piggybanks', $piggybanks)
            ->with('countRepeating', $countRepeating)
            ->with('countNonRepeating', $countNonRepeating)
            ->with('accounts', $accounts);
    }

    /**
     *
     */
    public function show(Piggybank $piggyBank)
    {
        $leftOnAccount = $this->_repository->leftOnAccount($piggyBank->account);
        $balance       = $piggyBank->account->balance();

        View::share('subTitle', $piggyBank->name);

        if ($piggyBank->repeats == 1) {
            // repeated expense.
            View::share('title', 'Repeated expenses');
            View::share('mainTitleIcon', 'fa-rotate-left');
        } else {
            // piggy bank.
            View::share('title', 'Piggy banks');
            View::share('mainTitleIcon', 'fa-sort-amount-asc');
        }

        return View::make('piggybanks.show')->with('piggyBank', $piggyBank)->with('leftOnAccount', $leftOnAccount)
            ->with('balance', $balance);
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function storePiggybank()
    {
        $data = Input::all();
        unset($data['_token']);

        // extend the data array with the settings needed to create a piggy bank:
        $data['repeats']   = 0;
        $data['rep_times'] = 1;
        $data['rep_every'] = 1;
        $data['order']     = 0;

        $piggyBank = $this->_repository->store($data);
        if (!is_null($piggyBank->id)) {
            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');
            Event::fire('piggybanks.store', [$piggyBank]);

            return Redirect::route('piggybanks.index.piggybanks');


        } else {
            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.create.piggybank')->withInput()->withErrors($piggyBank->errors());
        }

    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function storeRepeated()
    {

        $data = Input::all();
        unset($data['_token']);

        // extend the data array with the settings needed to create a repeated:
        $data['repeats'] = 1;
        $data['order']   = 0;

        $piggyBank = $this->_repository->store($data);
        if ($piggyBank->id) {
            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');
            Event::fire('piggybanks.store', [$piggyBank]);
            return Redirect::route('piggybanks.index.repeated');

        } else {
            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.create.repeated')->withInput()->withErrors($piggyBank->errors());
        }

    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Piggybank $piggyBank)
    {
        $piggyBank = $this->_repository->update($piggyBank, Input::all());
        if ($piggyBank->validate()) {
            Session::flash('success', 'Piggy bank "' . $piggyBank->name . '" updated.');
            Event::fire('piggybanks.update', [$piggyBank]);

            return Redirect::route('piggybanks.index');
        } else {
            Session::flash('error', 'Could not update piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.edit', $piggyBank->id)->withErrors($piggyBank->errors())->withInput();
        }


    }
}