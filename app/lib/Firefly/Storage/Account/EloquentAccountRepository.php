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
    public function get()
    {
        return \Auth::user()->accounts()->with('accounttype')->orderBy('name', 'ASC')->get();
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
    public function getByIds($ids)
    {
        if (count($ids) > 0) {
            return \Auth::user()->accounts()->with('accounttype')->whereIn('id', $ids)->orderBy('name', 'ASC')->get();
        } else {
            return [];
        }
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
    public function count()
    {
        return \Auth::user()->accounts()->count();

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
     * @param              $name
     * @param \AccountType $type
     *
     * @return \Account|mixed
     */
    public function createOrFind($name, \AccountType $type)
    {
        $beneficiary = $this->findByName($name);
        if (!$beneficiary) {
            $data = [
                'name' => $name,
                'account_type' => $type
            ];
            return $this->store($data);
        }
        return $beneficiary;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name)
    {
        return \Auth::user()->accounts()->where('name', 'like', '%' . $name . '%')->first();
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

    /**
     * @param $data
     *
     * @return \Account|void
     */
    public function update($data)
    {
        $account = $this->find($data['id']);
        if ($account) {
            // update account accordingly:
            $account->name = $data['name'];
            if ($account->validate()) {
                $account->save();
            }
            // update initial balance if necessary:
            if ($account->accounttype->description == 'Default account') {
                $journal = $this->findOpeningBalanceTransaction($account);
                $journal->date = new Carbon($data['openingbalancedate']);
                $journal->transactions[0]->amount = floatval($data['openingbalance']) * -1;
                $journal->transactions[1]->amount = floatval($data['openingbalance']);
                $journal->transactions[0]->save();
                $journal->transactions[1]->save();
                $journal->save();
            }
        }
        return $account;
    }

    public function destroy($accountId) {
        $account = $this->find($accountId);
        if($account) {
            $account->delete();
            return true;
        }
        return false;
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
     * @param \Account $account
     *
     * @return mixed|void
     */
    public function findOpeningBalanceTransaction(\Account $account)
    {
        $transactionType = \TransactionType::where('type', 'Opening balance')->first();
        $journal = \TransactionJournal::
            with(
                ['transactions' => function ($q) {
                        $q->orderBy('amount', 'ASC');
                    }]
            )->where('transaction_type_id', $transactionType->id)
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)->first(['transaction_journals.*']);
        return $journal;
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

}