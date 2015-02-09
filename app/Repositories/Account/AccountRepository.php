<?php

namespace FireflyIII\Repositories\Account;

use App;
use Config;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;

/**
 * Class AccountRepository
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountRepository implements AccountRepositoryInterface
{

    /**
     * @param array $data
     *
     * @return Account;
     */
    public function store(array $data)
    {
        $newAccount = $this->_store($data);


        // continue with the opposing account:
        if ($data['openingBalance'] != 0) {
            $type         = $data['openingBalance'] < 0 ? 'expense' : 'revenue';
            $opposingData = [
                'user'        => $data['user'],
                'accountType' => $type,
                'name'        => $data['name'] . ' initial balance',
                'active'      => false,
            ];
            $opposing     = $this->_store($opposingData);
            $this->_storeInitialBalance($newAccount, $opposing, $data);

        }

        return $newAccount;

    }

    /**
     * @param array $data
     *
     * @return Account
     */
    protected function _store(array $data)
    {
        $type        = Config::get('firefly.accountTypeByIdentifier.' . $data['accountType']);
        $accountType = AccountType::whereType($type)->first();
        $newAccount  = new Account(
            [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'name'            => $data['name'],
                'active'          => $data['active'] === true ? true : false,
            ]
        );
        if (!$newAccount->isValid()) {
            App::abort(500);
        }
        $newAccount->save();

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param Account $opposing
     * @param array   $data
     */
    protected function _storeInitialBalance(Account $account, Account $opposing, array $data)
    {
        $type            = $data['openingBalance'] < 0 ? 'Withdrawal' : 'Deposit';
        $transactionType = TransactionType::whereType($type)->first();

        $journal = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'bill_id'                 => null,
                'transaction_currency_id' => $data['openingBalanceCurrency'],
                'description'             => 'Initial balance for "' . $account->name . '"',
                'completed'               => true,
                'date'                    => $data['openingBalanceDate'],
                'encrypted'               => true
            ]
        );
        if (!$journal->isValid()) {
            App::abort(500);
        }
        $journal->save();


        if ($data['openingBalance'] < 0) {
            $firstAccount  = $opposing;
            $secondAccount = $account;
            $firstAmount   = $data['openingBalance'] * -1;
            $secondAmount  = $data['openingBalance'];
        } else {
            $firstAccount  = $account;
            $secondAccount = $opposing;
            $firstAmount   = $data['openingBalance'];
            $secondAmount  = $data['openingBalance'] * -1;
        }

        // first transaction: from
        $one = new Transaction(
            [
                'account_id'             => $firstAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $firstAmount
            ]
        );
        if (!$one->isValid()) {
            App::abort(500);
        }
        $one->save();

        // second transaction: to
        $two = new Transaction(
            [
                'account_id'             => $secondAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $secondAmount
            ]
        );
        if (!$two->isValid()) {
            App::abort(500);
        }
        $two->save();

        return $journal;

    }

}