<?php


namespace Firefly\Storage\Account;

use Firefly\Helper\MigrationException;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public $validator;

    public function __construct()
    {
    }

    public function get() {
        return \Auth::user()->accounts()->get();
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
        try {
            $initial->save();
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new FireflyException('Could not save counterbalance account for ' . $data['name']);
        }

        // create new transaction journal (and transactions):
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $transactionJournal */
        $transactionJournal = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');

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
        try {
            $account->save();
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new \Firefly\Exception\FireflyException('Could not save account ' . $data['name']);
        }

        return $account;
    }

}