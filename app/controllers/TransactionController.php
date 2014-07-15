<?php


use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class TransactionController extends BaseController {

    protected $accounts;

    public function __construct(ARI $accounts) {
        $this->accounts = $accounts;


        View::share('menu','home');
    }

    public function createWithdrawal() {

        // get accounts with names and id's.
        $accounts =$this->accounts->getActiveDefaultAsSelectList();


        return View::make('transactions.withdrawal')->with('accounts',$accounts);
    }

} 