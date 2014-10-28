<?php

namespace FireflyIII\Database;

use Carbon\Carbon;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;
use Illuminate\Support\Collection;

/**
 * Class Account
 *
 * @package FireflyIII\Database
 */
class Account implements CUD, CommonDatabaseCalls, AccountInterface
{
    use SwitchUser;

    public function __construct()
    {
        $this->setUser(\Auth::user());
    }

    /**
     * Get all asset accounts. Optional JSON based parameters.
     *
     * @param array $parameters
     *
     * @return Collection
     */
    public function getAssetAccounts(array $parameters = [])
    {
        return $this->getAccountsByType(['Default account', 'Asset account'], $parameters);

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
     * @param array $parameters
     *
     * @return Collection
     */
    public function getAccountsByType(array $types, array $parameters = [])
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
        $balanceOnDate = isset($parameters['date']) ? $parameters['date'] : Carbon::now();
        $query->where(
            function ($q) use ($balanceOnDate) {
                $q->where('transaction_journals.date', '<=', $balanceOnDate->format('Y-m-d'));
                $q->orWhereNull('transaction_journals.date');
            }
        );

        $query->groupBy('accounts.id');

        /*
         * If present, process parameters for sorting:
         */
        if (isset($parameters['order'])) {
            foreach ($parameters['order'] as $instr) {
                $query->orderBy($instr['name'], $instr['dir']);
            }
        }

        /*
         * If present, process parameters for searching.
         */
        if (isset($parameters['search'])) {
            $query->where('name', 'LIKE', '%' . e($parameters['search']['value'] . '%'));
        }

        /*
         * If present, start at $start:
         */
        if (isset($parameters['start'])) {
            $query->skip(intval($parameters['start']));
        }
        if (isset($parameters['length'])) {
            $query->take(intval($parameters['length']));
        }

        return $query->get(['accounts.*', \DB::Raw('SUM(`transactions`.`amount`) as `balance`')]);
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
     * @param array $types
     *
     * @return int
     */
    public function countAccountsByType(array $types)
    {
        return $this->getUser()->accounts()->accountTypeIn($types)->count();
    }

    /**
     * @param array $parameters
     *
     * @return Collection
     */
    public function getExpenseAccounts(array $parameters = [])
    {
        return $this->getAccountsByType(['Expense account', 'Beneficiary account'], $parameters);
    }

    /**
     * Get all default accounts.
     *
     * @return Collection
     */
    public function getDefaultAccounts()
    {
        // TODO: Implement getDefaultAccounts() method.
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
        // TODO: Implement find() method.
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        // TODO: Implement get() method.
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
     * Get all revenue accounts.
     *
     * @param array $parameters
     *
     * @return Collection
     */
    public function getRevenueAccounts(array $parameters = [])
    {
        return $this->getAccountsByType(['Revenue account'], $parameters);
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
     * @param \Account $account
     *
     * @return \TransactionJournal|null
     */
    public function openingBalanceTransaction(\Account $account)
    {
        return \TransactionJournal::withRelevantData()
                                  ->accountIs($account)
                                  ->leftJoin(
                                      'transaction_types', 'transaction_types.id', '=',
                                      'transaction_journals.transaction_type_id'
                                  )
                                  ->where('transaction_types.type', 'Opening balance')
                                  ->first(['transaction_journals.*']);
    }

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param Ardent $model
     *
     * @return array
     */
    public function validateObject(Ardent $model)
    {
        die('No impl');
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
        return [
            'errors'    => $errors,
            'warnings'  => $warnings,
            'successes' => $successes
        ];
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


        $data    = array_except($data, array('_token', 'what'));
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
     * @param \Account $account
     * @param array    $data
     *
     * @return bool
     */
    public function storeInitialBalance(\Account $account, array $data)
    {
        $opposingData    = [
            'name'   => $account->name . ' Initial Balance',
            'active' => 0,
            'what'   => 'initial'
        ];
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
        $opening = [
            'what'        => 'opening',
            'currency'    => 'EUR',
            'amount'      => $balance,
            'from'        => $from,
            'to'          => $to,
            'date'        => $date,
            'description' => 'Opening balance for new account ' . $account->name,
        ];


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
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        // TODO: Implement findByWhat() method.
    }
}