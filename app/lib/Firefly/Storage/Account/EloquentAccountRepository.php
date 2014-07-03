<?php


namespace Firefly\Storage\Account;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public $validator;

    public function __construct()
    {
    }

    public function count()
    {
        return \Auth::user()->accounts()->count();

    }

    public function storeWithInitialBalance($data, \Carbon\Carbon $date, $amount = 0)
    {

        $account = $this->store($data);

        $initialBalanceAT = \AccountType::where('description', 'Initial balance account')->first();
        $initial = new \Account;
        $initial->accountType()->associate($initialBalanceAT);
        $initial->user()->associate(\Auth::user());
        $initial->name = $data['name'] . ' initial balance';
        $initial->active = 0;
        $initial->save();

        // create new transaction journal (and transactions):
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalInterface $transactionJournal */
        $transactionJournal = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalInterface');
        $transactionJournal->createSimpleJournal(
            $initial, $account, 'Initial Balance for ' . $data['name'], $amount, $date
        );


        return $account;


    }

    public function store($data)
    {
        $defaultAT = \AccountType::where('description', 'Default account')->first();

        $at = isset($data['account_type']) ? $data['account_type'] : $defaultAT;

        $account = new \Account;
        $account->accountType()->associate($at);
        $account->user()->associate(\Auth::user());
        $account->name = $data['name'];
        $account->active = isset($data['active']) ? $data['active'] : 1;
        $account->save();
        return $account;
    }

}