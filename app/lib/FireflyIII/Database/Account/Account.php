<?php

namespace FireflyIII\Database\Account;

use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class Account
 *
 * @package    FireflyIII\Database
 * @implements FireflyIII\Database\Account\AccountInterface
 */
class Account implements CUD, CommonDatabaseCalls, AccountInterface
{
    use SwitchUser;

    /**
     *
     */
    public function __construct()
    {
        $this->setUser(\Auth::user());
    }

    /**
     * @param array $types
     *
     * @return int
     */
    public function countAccountsByType(array $types)
    {
        return $this->getUser()->accounts()->accountTypeIn($types)->count();
    }

    /**
     * @return int
     */
    public function countAssetAccounts()
    {
        return $this->countAccountsByType(['Default account', 'Asset account']);
    }

    /**
     * @return int
     */
    public function countExpenseAccounts()
    {
        return $this->countAccountsByType(['Expense account', 'Beneficiary account']);
    }

    /**
     * Counts the number of total revenue accounts. Useful for DataTables.
     *
     * @return int
     */
    public function countRevenueAccounts()
    {
        return $this->countAccountsByType(['Revenue account']);
    }

    /**
     * @param \Account $account
     *
     * @return \Account|null
     */
    public function findInitialBalanceAccount(\Account $account)
    {
        /** @var \FireflyIII\Database\AccountType\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $acctType->findByWhat('initial');

        return $this->getUser()->accounts()->where('account_type_id', $accountType->id)->where('name', 'LIKE', $account->name . '%')->first();
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types)
    {
        /*
         * Basic query:
         */
        $query = $this->getUser()->accounts()->accountTypeIn($types)->withMeta()->orderBy('name', 'ASC');;
        $set = $query->get(['accounts.*']);

        $set->each(
            function (\Account $account) {
                /*
                 * Get last activity date.
                 */
                $account->lastActivityDate = $this->getLastActivity($account);
            }
        );

        return $set;
    }

    /**
     * Get all asset accounts. Optional JSON based parameters.
     *
     * @param array $metaFilter
     *
     * @return Collection
     */
    public function getAssetAccounts($metaFilter = [])
    {
        $list = $this->getAccountsByType(['Default account', 'Asset account']);
        $list->each(
            function (\Account $account) {

                // get accountRole:

                /** @var \AccountMeta $entry */
                $accountRole = $account->accountmeta()->whereName('accountRole')->first();
                if (!$accountRole) {
                    $accountRole             = new \AccountMeta;
                    $accountRole->account_id = $account->id;
                    $accountRole->name       = 'accountRole';
                    $accountRole->data       = 'defaultExpense';
                    $accountRole->save();

                }
                $account->accountRole = $accountRole->data;
            }
        );

        return $list;

    }

    /**
     * @return Collection
     */
    public function getExpenseAccounts()
    {
        return $this->getAccountsByType(['Expense account', 'Beneficiary account']);
    }

    /**
     * Get all revenue accounts.
     *
     * @return Collection
     */
    public function getRevenueAccounts()
    {
        return $this->getAccountsByType(['Revenue account']);
    }

    /**
     * @param \Account $account
     *
     * @return \TransactionJournal|null
     */
    public function openingBalanceTransaction(\Account $account)
    {
        return \TransactionJournal::withRelevantData()->accountIs($account)->leftJoin(
            'transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id'
        )->where('transaction_types.type', 'Opening balance')->first(['transaction_journals.*']);
    }

    /**
     * @param \Account $account
     * @param array    $data
     *
     * @return bool
     */
    public function storeInitialBalance(\Account $account, array $data)
    {
        $opposingData    = ['name' => $account->name . ' Initial Balance', 'active' => 0, 'what' => 'initial'];
        $opposingAccount = $this->store($opposingData);

        /*
         * Create a journal from opposing to account or vice versa.
         */
        $balance = floatval($data['openingbalance']);
        $date    = new Carbon($data['openingbalancedate']);
        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $tj */
        $tj = \App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');
        if ($balance < 0) {
            // first transaction draws money from the new account to the opposing
            $from = $account;
            $to   = $opposingAccount;
        } else {
            // first transaction puts money into account
            $from = $opposingAccount;
            $to   = $account;
        }

        // data for transaction journal:
        $balance = $balance < 0 ? $balance * -1 : $balance;
        $opening = ['what'        => 'opening', 'currency' => 'EUR', 'amount' => $balance, 'from' => $from, 'to' => $to, 'date' => $date,
                    'description' => 'Opening balance for new account ' . $account->name,];


        $validation = $tj->validate($opening);
        if ($validation['errors']->count() == 0) {
            $tj->store($opening);

            return true;
        } else {
            var_dump($validation['errors']);
            exit;
        }

    }

    /**
     * @param Eloquent $model
     *
     * @return bool
     */
    public function destroy(Eloquent $model)
    {

        // delete journals:
        $journals = \TransactionJournal::whereIn(
            'id', function ($query) use ($model) {
            $query->select('transaction_journal_id')
                  ->from('transactions')->whereIn(
                    'account_id', function ($query) use ($model) {
                    $query
                        ->select('id')
                        ->from('accounts')
                        ->where(
                            function ($q) use ($model) {
                                $q->where('id', $model->id);
                                $q->orWhere(
                                    function ($q) use ($model) {
                                        $q->where('accounts.name', 'LIKE', '%' . $model->name . '%');
                                        // TODO magic number!
                                        $q->where('accounts.account_type_id', 3);
                                        $q->where('accounts.active', 0);
                                    }
                                );
                            }
                        )->where('accounts.user_id', $this->getUser()->id);
                }
                )->get();
        }
        )->get();
        /*
         * Get all transactions.
         */
        $transactions = [];
        /** @var \TransactionJournal $journal */
        foreach ($journals as $journal) {
            /** @var \Transaction $t */
            foreach ($journal->transactions as $t) {
                $transactions[] = intval($t->id);
            }
            $journal->delete();
        }
        // also delete transactions.
        if (count($transactions) > 0) {
            \Transaction::whereIn('id', $transactions)->delete();
        }


        /*
         * Trigger deletion:
         */
        \Event::fire('account.destroy', [$model]);

        // delete accounts:
        \Account::where(
            function ($q) use ($model) {
                $q->where('id', $model->id);
                $q->orWhere(
                    function ($q) use ($model) {
                        $q->where('accounts.name', 'LIKE', '%' . $model->name . '%');
                        // TODO magic number!
                        $q->where('accounts.account_type_id', 3);
                        $q->where('accounts.active', 0);
                    }
                );
            }
        )->delete();

        return true;

    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     */
    public function store(array $data)
    {

        /*
         * Find account type.
         */
        /** @var \FireflyIII\Database\AccountType\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $acctType->findByWhat($data['what']);

        $data['user_id']         = $this->getUser()->id;
        $data['account_type_id'] = $accountType->id;
        $data['active']          = isset($data['active']) && $data['active'] === '1' ? 1 : 0;


        $data    = array_except($data, ['_token', 'what']);
        $account = new \Account($data);
        if (!$account->isValid()) {
            var_dump($account->getErrors()->all());
            exit;
        }
        $account->save();
        if (isset($data['openingbalance']) && floatval($data['openingbalance']) != 0) {
            $this->storeInitialBalance($account, $data);
        }

        // TODO this needs cleaning up and thinking over.
        switch ($account->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $account->updateMeta('accountRole', $data['account_role']);
                break;
        }


        /* Tell transaction journal to store a new one.*/
        \Event::fire('account.store', [$account]);


        return $account;

    }

    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     */
    public function update(Eloquent $model, array $data)
    {
        $model->name   = $data['name'];
        $model->active = isset($data['active']) ? intval($data['active']) : 0;

        // TODO this needs cleaning up and thinking over.
        switch ($model->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $model->updateMeta('accountRole', $data['account_role']);
                break;
        }

        $model->save();

        if (isset($data['openingbalance']) && isset($data['openingbalancedate']) && strlen($data['openingbalancedate']) > 0) {
            $openingBalance = $this->openingBalanceTransaction($model);
            // TODO this needs cleaning up and thinking over.
            if (is_null($openingBalance)) {
                $this->storeInitialBalance($model, $data);
            } else {
                $openingBalance->date = new Carbon($data['openingbalancedate']);
                $openingBalance->save();
                $amount = floatval($data['openingbalance']);
                /** @var \Transaction $transaction */
                foreach ($openingBalance->transactions as $transaction) {
                    if ($transaction->account_id == $model->id) {
                        $transaction->amount = $amount;
                    } else {
                        $transaction->amount = $amount * -1;
                    }
                    $transaction->save();
                }
            }
        }
        \Event::fire('account.update', [$model]);

        return true;
    }

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $errors    = new MessageBag;

        /*
         * Name validation:
         */
        if (!isset($model['name'])) {
            $errors->add('name', 'Name is mandatory');
        }

        if (isset($model['name']) && strlen($model['name']) == 0) {
            $errors->add('name', 'Name is too short');
        }
        if (isset($model['name']) && strlen($model['name']) > 100) {
            $errors->add('name', 'Name is too long');
        }
        $validator = \Validator::make([$model], \Account::$rules);
        if ($validator->invalid()) {
            $errors->merge($errors);
        }

        if (isset($model['account_role']) && !in_array($model['account_role'], array_keys(\Config::get('firefly.accountRoles')))) {
            $errors->add('account_role', 'Invalid account role');
        } else {
            $successes->add('account_role', 'OK');
        }

        /*
         * type validation.
         */
        if (!isset($model['what'])) {
            $errors->add('name', 'Internal error: need to know type of account!');
        }

        /*
         * Opening balance and opening balance date.
         */
        if (isset($model['what']) && $model['what'] == 'asset') {
            if (isset($model['openingbalance']) && strlen($model['openingbalance']) > 0 && !is_numeric($model['openingbalance'])) {
                $errors->add('openingbalance', 'This is not a number.');
            }
            if (isset($model['openingbalancedate']) && strlen($model['openingbalancedate']) > 0) {
                try {
                    new Carbon($model['openingbalancedate']);
                } catch (\Exception $e) {
                    $errors->add('openingbalancedate', 'This date is invalid.');
                }
            }
        }


        if (!$errors->has('name')) {
            $successes->add('name', 'OK');
        }
        if (!$errors->has('openingbalance')) {
            $successes->add('openingbalance', 'OK');
        }
        if (!$errors->has('openingbalancedate')) {
            $successes->add('openingbalancedate', 'OK');
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     */
    public function find($objectId)
    {
        return $this->getUser()->accounts()->find($objectId);
    }

    /**
     * @param $what
     *
     * @throws NotImplementedException
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        // TODO: Implement findByWhat() method.
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function get()
    {
        // TODO: Implement get() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids)
    {
        return $this->getUser()->accounts()->whereIn('id', $ids)->get();
    }

    /**
     * @param $name
     *
     * @return static
     * @throws \FireflyIII\Exception\FireflyException
     */
    public function firstExpenseAccountOrCreate($name)
    {
        /** @var \FireflyIII\Database\AccountType\AccountType $accountTypeRepos */
        $accountTypeRepos = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $accountTypeRepos->findByWhat('expense');

        // if name is "", find cash account:
        if (strlen($name) == 0) {
            $cashAccountType = $accountTypeRepos->findByWhat('cash');

            // find or create cash account:
            return \Account::firstOrCreate(
                ['name' => 'Cash account', 'account_type_id' => $cashAccountType->id, 'active' => 0, 'user_id' => $this->getUser()->id,]
            );
        }

        $data = ['user_id' => $this->getUser()->id, 'account_type_id' => $accountType->id, 'name' => $name, 'active' => 1];

        return \Account::firstOrCreate($data);

    }

    /**
     * @param $name
     *
     * @return static
     * @throws \FireflyIII\Exception\FireflyException
     */
    public function firstRevenueAccountOrCreate($name)
    {
        /** @var \FireflyIII\Database\AccountType\AccountType $accountTypeRepos */
        $accountTypeRepos = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $accountTypeRepos->findByWhat('revenue');

        $data = ['user_id' => $this->getUser()->id, 'account_type_id' => $accountType->id, 'name' => $name, 'active' => 1];

        return \Account::firstOrCreate($data);

    }

    /**
     * @param \Account $account
     * @param int      $limit
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getAllTransactionJournals(\Account $account, $limit = 50)
    {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;
        $set    = $this->getUser()->transactionJournals()->withRelevantData()->leftJoin(
            'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
        )->where('transactions.account_id', $account->id)->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(
            ['transaction_journals.*']
        );
        $count  = $this->getUser()->transactionJournals()->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                       ->orderBy('date', 'DESC')->where('transactions.account_id', $account->id)->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);


    }

    /**
     * @param \Account $account
     *
     * @return int
     */
    public function getLastActivity(\Account $account)
    {
        $lastActivityKey = 'account.' . $account->id . '.lastActivityDate';
        if (\Cache::has($lastActivityKey)) {
            return \Cache::get($lastActivityKey);
        }

        $transaction = $account->transactions()
                               ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->orderBy('transaction_journals.date', 'DESC')->first();
        if ($transaction) {
            $date = $transaction->transactionJournal->date;
        } else {
            $date = 0;
        }
        \Cache::forever($lastActivityKey, $date);

        return $date;
    }

    /**
     * @param \Account $account
     * @param int      $limit
     * @param string   $range
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getTransactionJournals(\Account $account, $limit = 50, $range = 'session')
    {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;
        $items  = [];
        $query  = $this->getUser()
                       ->transactionJournals()
                       ->withRelevantData()
                       ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                       ->where('transactions.account_id', $account->id)
                       ->orderBy('date', 'DESC');

        if ($range == 'session') {
            $query->before(\Session::get('end', Carbon::now()->startOfMonth()));
            $query->after(\Session::get('start', Carbon::now()->startOfMonth()));
        }
        $count = $query->count();
        $set   = $query->take($limit)->offset($offset)->get(['transaction_journals.*']);

        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);


    }

    /**
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getTransactionJournalsInRange(\Account $account, Carbon $start, Carbon $end)
    {
        $set = $this->getUser()->transactionJournals()->transactionTypes(['Withdrawal'])->withRelevantData()->leftJoin(
            'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
        )->where('transactions.account_id', $account->id)->before($end)->after($start)->orderBy('date', 'DESC')->get(
            ['transaction_journals.*']
        );

        return $set;

    }


}