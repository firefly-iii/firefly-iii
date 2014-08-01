<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Piggybank\PiggybankRepositoryInterface as PRI;

/**
 * Class PiggybankController
 */
class PiggybankController extends BaseController
{

    protected $_repository;
    protected $_accounts;

    public function __construct(PRI $repository, ARI $accounts)
    {
        $this->_repository = $repository;
        $this->_accounts = $accounts;
        View::share('menu', 'home');

    }

    public function create()
    {
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        return View::make('piggybanks.create')->with('accounts', $accounts);
    }

    public function delete()
    {
    }

    public function destroy()
    {
    }

    public function edit()
    {
    }
    public function updateAmount(Piggybank $piggybank) {
        $this->_repository->updateAmount($piggybank,Input::get('amount'));
    }

    public function index()
    {
        $count = $this->_repository->count();
        $piggybanks = $this->_repository->get();
        $accounts = [];
        // get accounts:
        foreach ($piggybanks as $piggyBank) {
            $account = $piggyBank->account;
            $piggyBank->pct = round(($piggyBank->amount / $piggyBank->target) * 100, 0) . '%';
            $id = $account->id;
            if (!isset($accounts[$id])) {
                $account->balance = $account->balance();
                $account->left = $account->balance - $piggyBank->amount;
            } else {
                $account->left -= $piggyBank->amount;

            }
            $accounts[$id] = $account;
        }


        return View::make('piggybanks.index')->with('count', $count)->with('accounts', $accounts)->with(
            'piggybanks', $piggybanks
        );
    }

    public function show()
    {
    }

    public function store()
    {
        $piggyBank = $this->_repository->store(Input::all());
        if (!$piggyBank->id) {
            Session::flash('error', 'Could not save piggy bank: ' . $piggyBank->errors()->first());

            return Redirect::route('piggybanks.create')->withInput();
        } else {
            Session::flash('success', 'New piggy bank created!');

            return Redirect::route('piggybanks.index');
        }

    }

    public function update()
    {
    }
}