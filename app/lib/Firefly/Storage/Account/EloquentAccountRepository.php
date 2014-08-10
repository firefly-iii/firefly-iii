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
    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return \Auth::user()->accounts()->count();

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
        $type = \AccountType::where('description', 'Beneficiary account')->first();

        return $this->createOrFind($name, $type);
    }

    /**
     * @param \Account $account
     *
     * @return bool|mixed
     */
    public function destroy(\Account $account)
    {
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
        return \Auth::user()->accounts()->where('id', $accountId)->first();
    }

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return mixed
     */
    public function findByName($name, \AccountType $type = null)
    {
        $type = is_null($type) ? \AccountType::where('description', 'Default account')->first() : $type;

        return \Auth::user()->accounts()->where('account_type_id', $type->id)->where('name', 'like', '%' . $name . '%')
            ->first();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return \Auth::user()->accounts()->with('accounttype')->orderBy('name', 'ASC')->get();
    }

    /**
     * @return mixed
     */
    public function getActiveDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')->where('accounts.active', 1)

            ->get(['accounts.*']);
    }

    /**
     * @return array|mixed
     */
    public function  getActiveDefaultAsSelectList()
    {
        $list = \Auth::user()->accounts()->leftJoin(
            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
            ->where('account_types.description', 'Default account')->where('accounts.active', 1)

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
        $list = \Auth::user()->accounts()->leftJoin(
            'account_types', 'account_types.id', '=', 'accounts.account_type_id'
        )
            ->where('account_types.description', 'Beneficiary account')->where('accounts.active', 1)

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
            return \Auth::user()->accounts()->with('accounttype')->whereIn('id', $ids)->orderBy('name', 'ASC')->get();
        } else {
            return $this->getActiveDefault();
        }
    }

    /**
     * @return mixed
     */
    public function getCashAccount()
    {
        $type = \AccountType::where('description', 'Cash account')->first();
        $cash = \Auth::user()->accounts()->where('account_type_id', $type->id)->first();

        return $cash;

    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return \Auth::user()->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('account_types.description', 'Default account')

            ->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
    }

    /**
     * @param $data
     *
     * @return \Account
     * @throws \Firefly\Exception\FireflyException
     */
    public function store($data)
    {
        $defaultAccountType = \AccountType::where('description', 'Default account')->first();
        $accountType = isset($data['account_type']) ? $data['account_type'] : $defaultAccountType;

        // create Account:
        $account = new \Account;
        $account->accountType()->associate($accountType);
        $account->user()->associate(\Auth::user());
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
                $date = new Carbon($data['openingbalancedate']);
                $this->_createInitialBalance($account, $amount, $date);
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

            if ($account->accounttype->description == 'Default account') {


                $journal = $interface->openingBalanceTransaction($account);
                if ($journal) {
                    $journal->date = new Carbon($data['openingbalancedate']);
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
     * @param int      $amount
     * @param Carbon   $date
     *
     * @return bool
     */
    protected function _createInitialBalance(\Account $account, $amount = 0, Carbon $date)
    {
        // get account type:
        $initialBalanceAT = \AccountType::where('description', 'Initial balance account')->first();

        // create new account:
        $initial = new \Account;
        $initial->accountType()->associate($initialBalanceAT);
        $initial->user()->associate(\Auth::user());
        $initial->name = $account->name . ' initial balance';
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