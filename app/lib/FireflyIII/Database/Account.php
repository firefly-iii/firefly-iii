<?php

namespace FireflyIII\Database;

use Carbon\Carbon;
use FireflyIII\Database\Ifaces\AccountInterface;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class Account
 *
 * @package FireflyIII\Database
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
        /** @var \FireflyIII\Database\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType');

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
        $query = $this->getUser()->accounts()->accountTypeIn($types);


        /*
         * Without an opening balance, the rest of these queries will fail.
         */

        $query->leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id');
        $query->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id');

        /*
         * Not used, but useful for the balance within a certain month / year.
         */
        $query->where(
            function ($q) {
                $q->where('transaction_journals.date', '<=', Carbon::now()->format('Y-m-d'));
                $q->orWhereNull('transaction_journals.date');
            }
        );

        $query->groupBy('accounts.id');

        /*
         * If present, process parameters for sorting:
         */
        $query->orderBy('name', 'ASC');

        return $query->get(['accounts.*', \DB::Raw('SUM(`transactions`.`amount`) as `balance`')]);
    }

    /**
     * Get all asset accounts. Optional JSON based parameters.
     *
     * @param array $parameters
     *
     * @return Collection
     */
    public function getAssetAccounts()
    {
        return $this->getAccountsByType(['Default account', 'Asset account']);

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
        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = \App::make('FireflyIII\Database\TransactionJournal');
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

        return false;
    }

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        $model->delete();

        return true;

    }

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data)
    {

        /*
         * Find account type.
         */
        /** @var \FireflyIII\Database\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType');

        $accountType = $acctType->findByWhat($data['what']);

        $data['user_id']         = $this->getUser()->id;
        $data['account_type_id'] = $accountType->id;
        $data['active']          = isset($data['active']) && $data['active'] === '1' ? 1 : 0;


        $data    = array_except($data, ['_token', 'what']);
        $account = new \Account($data);
        if (!$account->validate()) {
            var_dump($account->errors()->all());
            exit;
        }
        $account->save();
        if (isset($data['openingbalance']) && floatval($data['openingbalance']) != 0) {
            $this->storeInitialBalance($account, $data);
        }


        /* Tell transaction journal to store a new one.*/


        return $account;

    }

    /**
     * @param Ardent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data)
    {
        $model->name   = $data['name'];
        $model->active = isset($data['active']) ? intval($data['active']) : 0;
        $model->save();

        if (isset($data['openingbalance']) && isset($data['openingbalancedate'])) {
            $openingBalance = $this->openingBalanceTransaction($model);

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
     * @param int $id
     *
     * @return Ardent
     */
    public function find($id)
    {
        return $this->getUser()->accounts()->find($id);
    }

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
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

    public function firstExpenseAccountOrCreate($name)
    {
        /** @var \FireflyIII\Database\AccountType $accountTypeRepos */
        $accountTypeRepos = \App::make('FireflyIII\Database\AccountType');

        $accountType = $accountTypeRepos->findByWhat('expense');

        // if name is "", find cash account:
        if (strlen($name) == 0) {
            $cashAccountType = $accountTypeRepos->findByWhat('cash');

            // find or create cash account:
            return \Account::firstOrCreate(
                ['name' => 'Cash account', 'account_type_id' => $cashAccountType->id, 'active' => 1, 'user_id' => $this->getUser()->id,]
            );
        }

        $data = ['user_id' => $this->getUser()->id, 'account_type_id' => $accountType->id, 'name' => $name, 'active' => 1];

        return \Account::firstOrCreate($data);

    }

    public function firstRevenueAccountOrCreate($name)
    {
        /** @var \FireflyIII\Database\AccountType $accountTypeRepos */
        $accountTypeRepos = \App::make('FireflyIII\Database\AccountType');

        $accountType = $accountTypeRepos->findByWhat('revenue');

        $data = ['user_id' => $this->getUser()->id, 'account_type_id' => $accountType->id, 'name' => $name, 'active' => 1];

        return \Account::firstOrCreate($data);

    }

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

    public function getTransactionJournals(\Account $account, $limit = 50)
    {
        $start  = \Session::get('start');
        $end    = \Session::get('end');
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;
        $set    = $this->getUser()->transactionJournals()->withRelevantData()->leftJoin(
            'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
        )->where('transactions.account_id', $account->id)->take($limit)->offset($offset)->before($end)->after($start)->orderBy('date', 'DESC')->get(
            ['transaction_journals.*']
        );
        $count  = $this->getUser()->transactionJournals()->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                       ->before($end)->after($start)->orderBy('date', 'DESC')->where('transactions.account_id', $account->id)->count();
        $items  = [];
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