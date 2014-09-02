<?php


namespace Firefly\Storage\Account;

use Carbon\Carbon;

/**
 * Class EloquentAccountRepository
 *
 * @package Firefly\Storage\Account
 */
class EloquentAccountRepository implements AccountRepositoryInterface
{

    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return $this->_user->accounts()->count();

    }

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return \Account|mixed
     */
    public function createOrFind($name, \AccountType $type = null)
    {
        $account = $this->findByName($name, $type);
        if (!$account) {
            $data = [
                'name'         => $name,
                'account_type' => $type
            ];

            return $this->store($data);
        }

        return $account;
    }

    /**
     * @param $name
     *
     * @return \Account|mixed|null
     */
    public function createOrFindBeneficiary($name)
    {
        if (is_null($name) || strlen($name) == 0) {
            return null;
        }
        $type = \AccountType::where('type', 'Beneficiary account')->first();
        return $this->createOrFind($name, $type);
    }

    /**
     * @param \Account $account
     *
     * @return bool|mixed
     */
    public function destroy(\Account $account)
    {
        // find all transaction journals related to this account:
        $journals   = \TransactionJournal::withRelevantData()->account($account)->get(['transaction_journals.*']);
        $accountIDs = [];

        /** @var \TransactionJournal $journal */
        foreach ($journals as $journal) {
            // remember the account id's of the transactions involved:
            foreach ($journal->transactions as $t) {
                $accountIDs[] = $t->account_id;
            }
            $journal->delete();

        }
        $accountIDs = array_unique($accountIDs);
        if (count($accountIDs) > 0) {
            // find the "initial balance" type accounts in this list. Should be just 1.
            $query = $this->_user->accounts()->accountTypeIn(['Initial balance account'])
                                 ->whereIn('accounts.id', $accountIDs);
            if ($query->count() == 1) {
                $iba = $query->first(['accounts.*']);
                $iba->delete();
            }
        }
        $account->delete();

        /**
         *
         * TODO
         * Also delete: initial balance, initial balance account, and transactions
         */

        return true;
    }

    /**
     * @param $accountId
     *
     * @return mixed
     */
    public function find($accountId)
    {
        return $this->_user->accounts()->where('id', $accountId)->first();
    }

    /**
     * @param $type
     * @return mixed
     */
    public function findAccountType($type)
    {
        return \AccountType::where('type', $type)->first();
    }

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return mixed
     */
    public function findByName($name, \AccountType $type = null)
    {
        $type = is_null($type) ? \AccountType::where('type', 'Default account')->first() : $type;

        return $this->_user->accounts()->where('account_type_id', $type->id)
                           ->where('name', 'like', '%' . $name . '%')
                           ->first();
    }

    /**
     * Used for import
     *
     * @param              $name
     *
     * @return mixed
     */
    public function findByNameAny($name)
    {
        return $this->_user->accounts()
                           ->where('name', 'like', '%' . $name . '%')
                           ->first();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->_user->accounts()->with('accounttype')->orderBy('name', 'ASC')->get();
    }

    /**
     * @return mixed
     */
    public function getActiveDefault()
    {
        return $this->_user->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->where('account_types.type', 'Default account')->where('accounts.active', 1)

                           ->get(['accounts.*']);
    }

    /**
     * @return array|mixed
     */
    public function  getActiveDefaultAsSelectList()
    {
        $list   = $this->_user->accounts()->leftJoin(
                              'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
                              ->where('account_types.type', 'Default account')->where('accounts.active', 1)

                              ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
        $return = [];
        foreach ($list as $entry) {
            $return[intval($entry->id)] = $entry->name;
        }

        return $return;
    }

    /**
     * @return mixed
     */
    public function getBeneficiaries()
    {
        $list = $this->_user->accounts()->leftJoin(
                            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
                            ->where('account_types.type', 'Beneficiary account')->where('accounts.active', 1)

                            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);

        return $list;
    }

    /**
     * @param $ids
     *
     * @return array|mixed
     */
    public function getByIds(array $ids)
    {
        if (count($ids) > 0) {
            return $this->_user->accounts()->with('accounttype')->whereIn('id', $ids)->orderBy('name', 'ASC')->get();
        } else {
            return $this->getActiveDefault();
        }
    }

    /**
     * @return mixed
     */
    public function getCashAccount()
    {
        $type = \AccountType::where('type', 'Cash account')->first();
        $cash = $this->_user->accounts()->where('account_type_id', $type->id)->first();
        if (is_null($cash)) {
            $cash = new \Account;
            $cash->accountType()->associate($type);
            $cash->user()->associate($this->_user);
            $cash->name   = 'Cash account';
            $cash->active = 1;
            $cash->save();
        }

        return $cash;

    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->_user->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->where('account_types.type', 'Default account')

                           ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
    }

    /**
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    /**
     * @param $data
     *
     * @return \Account
     * @throws \Firefly\Exception\FireflyException
     */
    public function store($data)
    {
        /**
         * If the AccountType has been passed through, use it:
         */
        if (isset($data['account_type']) && is_object($data['account_type'])
            && get_class($data['account_type']) == 'AccountType'
        ) {
            $accountType = $data['account_type'];
        } else if (isset($data['account_type']) && is_string($data['account_type'])) {
            $accountType = \AccountType::where('type', $data['account_type'])->first();

        } else {
            $accountType = \AccountType::where('type', 'Default account')->first();
        }

        /**
         * Create new account:
         */
        $account = new \Account;
        $account->accountType()->associate($accountType);
        $account->user()->associate($this->_user);

        $account->name = $data['name'];
        $account->active
                       = isset($data['active']) && intval($data['active']) >= 0 && intval($data['active']) <= 1 ? intval(
            $data['active']
        ) : 1;

        // try to save it:
        if ($account->save()) {
            // create initial balance, if necessary:
            if (isset($data['openingbalance']) && isset($data['openingbalancedate'])) {
                $amount = floatval($data['openingbalance']);
                $date   = new Carbon($data['openingbalancedate']);
                if ($amount != 0) {
                    $this->_createInitialBalance($account, $amount, $date);
                }
            }
        }


        // whatever the result, return the account.
        return $account;
    }

    /**
     * @param \Account $account
     * @param          $data
     *
     * @return \Account|mixed
     */
    public function update(\Account $account, $data)
    {
        // update account accordingly:
        $account->name = $data['name'];
        if ($account->validate()) {
            $account->save();
        }
        // update initial balance if necessary:
        if (floatval($data['openingbalance']) != 0) {

            /** @var \Firefly\Helper\Controllers\AccountInterface $interface */
            $interface = \App::make('Firefly\Helper\Controllers\AccountInterface');

            if ($account->accounttype->type == 'Default account') {


                $journal = $interface->openingBalanceTransaction($account);
                if ($journal) {
                    $journal->date                    = new Carbon($data['openingbalancedate']);
                    $journal->transactions[0]->amount = floatval($data['openingbalance']) * -1;
                    $journal->transactions[1]->amount = floatval($data['openingbalance']);
                    $journal->transactions[0]->save();
                    $journal->transactions[1]->save();
                    $journal->save();
                }
            }
        }

        return $account;
    }

    /**
     * @param \Account $account
     * @param int $amount
     * @param Carbon $date
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _createInitialBalance(\Account $account, $amount = 0, Carbon $date)
    {
        // get account type:
        $initialBalanceAT = \AccountType::where('type', 'Initial balance account')->first();

        // create new account:
        $initial = new \Account;
        $initial->accountType()->associate($initialBalanceAT);
        $initial->user()->associate($this->_user);
        $initial->name   = $account->name . ' initial balance';
        $initial->active = 0;
        if ($initial->validate()) {
            $initial->save();
            // create new transaction journal (and transactions):
            /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $transactionJournal */
            $transactionJournal = \App::make(
                                      'Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface'
            );

            $transactionJournal->createSimpleJournal(
                               $initial, $account, 'Initial Balance for ' . $account->name, $amount, $date
            );

            return true;
        }

        return false;
    }

}