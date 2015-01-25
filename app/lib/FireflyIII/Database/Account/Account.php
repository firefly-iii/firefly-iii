<?php

namespace FireflyIII\Database\Account;

use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class Account
 *
 * @package    FireflyIII\Database
 * @implements FireflyIII\Database\Account\AccountInterface
 */
class Account implements CUDInterface, CommonDatabaseCallsInterface, AccountInterface
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
        $set = $query->get(['accounts.*', 'account_meta.data as accountRole']);

        $set->each(
            function (\Account $account) {
                /*
                 * Get last activity date.
                 */
                $account->lastActivityDate = $this->getLastActivity($account);
                $account->accountRole      = \Config::get('firefly.accountRoles.' . json_decode($account->accountRole));
            }
        );

        return $set;
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
        $balance         = floatval($data['openingBalance']);
        $date            = new Carbon($data['openingBalanceDate']);
        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $journals */
        $journals    = \App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');
        $fromAccount = $opposingAccount;
        $toAccount   = $account;
        if ($balance < 0) {
            $fromAccount = $account;
            $toAccount   = $opposingAccount;
        }
        // data for transaction journal:
        $balance = $balance < 0 ? $balance * -1 : $balance;

        /** @var \FireflyIII\Database\TransactionType\TransactionType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\TransactionType\TransactionType');
        $type           = $typeRepository->findByWhat('opening');
        $currency       = \Amount::getDefaultCurrency();

        $opening = ['transaction_type_id' => $type->id, 'transaction_currency_id' => $currency->id, 'amount' => $balance, 'from' => $fromAccount,
                    'completed'           => 0, 'currency' => 'EUR', 'what' => 'opening', 'to' => $toAccount, 'date' => $date,
                    'description'         => 'Opening balance for new account ' . $account->name,];

        $validation = $journals->validate($opening);
        if ($validation['errors']->count() == 0) {
            $journals->store($opening);

            return true;
        } else {
            \Log::error('Initial balance created is not valid (Database/Account)');
            \Log::error($validation['errors']->all());
            \App::abort(500);
        }

        return false;
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // cannot make it shorter because of query.
     * @param Eloquent $model
     *
     * @return bool
     */
    public function destroy(Eloquent $model)
    {
        $journals     = \TransactionJournal::whereIn(
            'id', function (QueryBuilder $query) use ($model) {
            $query->select('transaction_journal_id')
                  ->from('transactions')
                  ->whereIn(
                      'account_id', function (QueryBuilder $query) use ($model) {
                      $query
                          ->select('accounts.id')
                          ->from('accounts')
                          ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                          ->where(
                              function (QueryBuilder $q) use ($model) {
                                  $q->where('accounts.id', $model->id);
                                  $q->orWhere(
                                      function (QueryBuilder $q) use ($model) {
                                          $q->where('accounts.name', 'LIKE', '%' . $model->name . '%');
                                          $q->where('account_types.type', 'Initial balance account');
                                          $q->where('accounts.active', 0);
                                      }
                                  );
                              }
                          )->where('accounts.user_id', $this->getUser()->id);
                  }
                  )->get();
        }
        )->get();
        $transactions = [];
        /** @var \TransactionJournal $journal */
        foreach ($journals as $journal) {
            /** @var \Transaction $t */
            foreach ($journal->transactions as $t) {
                $transactions[] = intval($t->id);
            }
            $journal->delete();
        }
        if (count($transactions) > 0) {
            \Transaction::whereIn('id', $transactions)->delete();
        }
        \Event::fire('account.destroy', [$model]);

        // get account type:
        /** @var \FireflyIII\Database\AccountType\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $acctType->findByWhat('initial');

        //$q->where('account_types.type', '');

        \Account::where(
            function (EloquentBuilder $q) use ($model, $accountType) {
                $q->where('id', $model->id);
                $q->orWhere(
                    function (EloquentBuilder $q) use ($model, $accountType) {
                        $q->where('accounts.name', 'LIKE', '%' . $model->name . '%');
                        $q->where('accounts.account_type_id', $accountType->id);
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

        /** @var \FireflyIII\Database\AccountType\AccountType $acctType */
        $acctType = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $acctType->findByWhat($data['what']);

        $data['user_id']         = $this->getUser()->id;
        $data['account_type_id'] = $accountType->id;
        $data['active']          = isset($data['active']) && $data['active'] === '1' ? 1 : 0;

        $data    = array_except($data, ['_token', 'what']);
        $account = new \Account($data);
        if (!$account->isValid()) {
            \Log::error('Account created is not valid (Database/Account)');
            \Log::error($account->getErrors()->all());
            \App::abort(500);
        }
        $account->save();
        if (isset($data['openingBalance']) && floatval($data['openingBalance']) != 0) {
            $this->storeInitialBalance($account, $data);
        }

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

        switch ($model->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $model->updateMeta('accountRole', $data['account_role']);
                break;
        }

        $model->save();

        if (isset($data['openingBalance']) && isset($data['openingBalanceDate']) && strlen($data['openingBalanceDate']) > 0) {
            /** @noinspection PhpParamsInspection */
            $openingBalance = $this->openingBalanceTransaction($model);
            if (is_null($openingBalance)) {
                $this->storeInitialBalance($model, $data);
            } else {
                $openingBalance->date = new Carbon($data['openingBalanceDate']);
                $openingBalance->save();
                $amount = floatval($data['openingBalance']);
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
            if (isset($model['openingBalance']) && strlen($model['openingBalance']) > 0 && !is_numeric($model['openingBalance'])) {
                $errors->add('openingBalance', 'This is not a number.');
            }
            if (isset($model['openingBalanceDate']) && strlen($model['openingBalanceDate']) > 0) {
                try {
                    new Carbon($model['openingBalanceDate']);
                } catch (\Exception $e) {
                    $errors->add('openingBalanceDate', 'This date is invalid.');
                }
            }
        }


        if (!$errors->has('name')) {
            $successes->add('name', 'OK');
        }
        if (!$errors->has('openingBalance')) {
            $successes->add('openingBalance', 'OK');
        }
        if (!$errors->has('openingBalanceDate')) {
            $successes->add('openingBalanceDate', 'OK');
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codeCoverageIgnore
     *
     * @param $what
     *
     * @throws NotImplementedException
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     * @throws NotImplementedException
     * @codeCoverageIgnore
     */
    public function get()
    {
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
        /** @var \FireflyIII\Database\AccountType\AccountType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $typeRepository->findByWhat('expense');

        // if name is "", find cash account:
        if (strlen($name) == 0) {
            $cashAccountType = $typeRepository->findByWhat('cash');

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
        /** @var \FireflyIII\Database\AccountType\AccountType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\AccountType\AccountType');

        $accountType = $typeRepository->findByWhat('revenue');

        // if name is "", find cash account:
        if (strlen($name) == 0) {
            $cashAccountType = $typeRepository->findByWhat('cash');

            // find or create cash account:
            return \Account::firstOrCreate(
                ['name' => 'Cash account', 'account_type_id' => $cashAccountType->id, 'active' => 0, 'user_id' => $this->getUser()->id,]
            );
        }

        $data = ['user_id' => $this->getUser()->id, 'account_type_id' => $accountType->id, 'name' => $name, 'active' => 1];

        return \Account::firstOrCreate($data);

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

}
