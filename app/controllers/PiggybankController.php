<?php

use Carbon\Carbon;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Piggybank\PiggybankRepositoryInterface as PRI;

/**
 * Class PiggybankController
 */
class PiggybankController extends BaseController
{

    protected $_repository;
    protected $_accounts;

    /**
     * @param PRI $repository
     * @param ARI $accounts
     */
    public function __construct(PRI $repository, ARI $accounts)
    {
        $this->_repository = $repository;
        $this->_accounts = $accounts;
    }

    /**
     * @return $this
     */
    public function createPiggybank()
    {
        $periods = Config::get('firefly.piggybank_periods');
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        return View::make('piggybanks.create-piggybank')->with('accounts', $accounts)->with('periods',$periods);
    }

    public function createRepeated()
    {
        $periods = Config::get('firefly.piggybank_periods');
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        return View::make('piggybanks.create-repeated')->with('accounts', $accounts)->with('periods',$periods);
    }


    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function delete(Piggybank $piggyBank)
    {
        return View::make('piggybanks.delete')->with('piggybank', $piggyBank);
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Piggybank $piggyBank)
    {
        $piggyBank->delete();
        Session::flash('success', 'Piggy bank deleted.');

        return Redirect::route('piggybanks.index');
    }

    /**
     * @param Piggybank $piggyBank
     *
     * @return $this
     */
    public function edit(Piggybank $piggyBank)
    {
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        return View::make('piggybanks.edit')->with('piggybank', $piggyBank)->with('accounts', $accounts);
    }

    /**
     * @return $this
     */
    public function index()
    {
        $countRepeating = $this->_repository->countRepeating();
        $countNonRepeating = $this->_repository->countNonrepeating();
        $piggybanks = $this->_repository->get();
        return View::make('piggybanks.index')->with('piggybanks', $piggybanks)
            ->with('countRepeating',$countRepeating)
            ->with('countNonRepeating',$countNonRepeating);
    }

    /**
     *
     */
    public function show(Piggybank $piggyBank)
    {
        return View::make('piggybanks.show')->with('piggyBank', $piggyBank);
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function storePiggybank()
    {
        $data = Input::all();
        unset($data['_token']);

        // extend the data array with the settings needed to create a piggy bank:
        $data['repeats'] = 0;
        $data['rep_times'] = 0;
        $data['order'] = 0;

        $piggyBank = $this->_repository->store($data);
        if (!is_null($piggyBank->id)) {
            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');

            return Redirect::route('piggybanks.index');


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
        $data['startdate'] = new Carbon;
        $data['order'] = 0;

        $piggyBank = $this->_repository->store($data);
        if ($piggyBank->validate()) {
            Session::flash('success', 'New piggy bank "' . $piggyBank->name . '" created!');

            return Redirect::route('piggybanks.index');

        } else {
            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.create')->withInput();
        }

    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update()
    {

        $piggyBank = $this->_repository->update(Input::all());
        if ($piggyBank->validate()) {
            Session::flash('success', 'Piggy bank "' . $piggyBank->name . '" updated.');

            return Redirect::route('piggybanks.index');
        } else {
            Session::flash('error', 'Could not update piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.edit', $piggyBank->id)->withErrors($piggyBank->errors())->withInput();
        }


    }

    /**
     * @param Piggybank $piggybank
     */
    public function updateAmount(Piggybank $piggybank)
    {
        $this->_repository->updateAmount($piggybank, Input::get('amount'));
    }
}