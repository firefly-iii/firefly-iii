<?php

namespace FireflyIII\Repositories\Account;

use App;
use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Session;

/**
 * Class AccountRepository
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountRepository implements AccountRepositoryInterface
{

    /**
     * @param Account $account
     *
     * @return boolean
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return true;
    }

    /**
     * @param Account $account
     * @param int     $page
     * @param string  $range
     *
     * @return mixed
     */
    public function getJournals(Account $account, $page, $range = 'session')
    {
        $offset = $page * 50;
        $items  = [];
        $query  = Auth::user()
                      ->transactionJournals()
                      ->withRelevantData()
                      ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                      ->where('transactions.account_id', $account->id)
                      ->orderBy('date', 'DESC');

        if ($range == 'session') {
            $query->before(Session::get('end', Carbon::now()->startOfMonth()));
            $query->after(Session::get('start', Carbon::now()->startOfMonth()));
        }
        $count = $query->count();
        $set   = $query->take(50)->offset($offset)->get(['transaction_journals.*']);

        foreach ($set as $entry) {
            $items[] = $entry;
        }

        $paginator = new LengthAwarePaginator($items, $count, 50, $page);

        return $paginator;

        //return Paginator::make($items, $count, 50);


    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function openingBalanceTransaction(Account $account)
    {
        return TransactionJournal::accountIs($account)
                                 ->orderBy('transaction_journals.date', 'ASC')
                                 ->orderBy('created_at', 'ASC')
                                 ->first(['transaction_journals.*']);
    }

    /**
     * @param array $data
     *
     * @return Account;
     */
    public function store(array $data)
    {
        $newAccount = $this->_store($data);
        $this->_storeMetadata($newAccount, $data);


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
     * @param Account $account
     * @param array   $data
     */
    public function update(Account $account, array $data)
    {
        // update the account:
        $account->name   = $data['name'];
        $account->active = $data['active'] == '1' ? true : false;
        $account->save();

        // update meta data:
        /** @var AccountMeta $meta */
        foreach ($account->accountMeta()->get() as $meta) {
            if ($meta->name == 'accountRole') {
                $meta->data = $data['accountRole'];
                $meta->save();
            }
        }

        $openingBalance = $this->openingBalanceTransaction($account);

        // if has openingbalance?
        if ($data['openingBalance'] != 0) {
            // if opening balance, do an update:
            if ($openingBalance) {
                // update existing opening balance.
                $this->_updateInitialBalance($account, $openingBalance, $data);
            } else {
                // create new opening balance.
                $type         = $data['openingBalance'] < 0 ? 'expense' : 'revenue';
                $opposingData = [
                    'user'        => $data['user'],
                    'accountType' => $type,
                    'name'        => $data['name'] . ' initial balance',
                    'active'      => false,
                ];
                $opposing     = $this->_store($opposingData);
                $this->_storeInitialBalance($account, $opposing, $data);
            }

        } else {
            // opening balance is zero, should we delete it?
            if ($openingBalance) {
                // delete existing opening balance.
                $openingBalance->delete();
            }
        }

        return $account;
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
            // does the account already exist?
            $existingAccount = Account::where('user_id', $data['user'])->where('account_type_id', $accountType->id)->where('name', $data['name'])->first();
            if (!$existingAccount) {
                Log::error('Account create error: ' . $newAccount->getErrors()->toJson());
                var_dump($newAccount->getErrors()->toArray());
            }
            $newAccount = $existingAccount;
        }
        $newAccount->save();

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    protected function _storeMetadata(Account $account, array $data)
    {
        $metaData = new AccountMeta(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => $data['accountRole']
            ]
        );
        if (!$metaData->isValid()) {
            App::abort(500);
        }
        $metaData->save();
    }

    /**
     * @param Account $account
     * @param Account $opposing
     * @param array   $data
     *
     * @return TransactionJournal
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

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    protected function _updateInitialBalance(Account $account, TransactionJournal $journal, array $data)
    {
        $journal->date = $data['openingBalanceDate'];

        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($account->id == $transaction->account_id) {
                $transaction->amount = $data['openingBalance'];
                $transaction->save();
            }
            if ($account->id != $transaction->account_id) {
                $transaction->amount = $data['openingBalance'] * -1;
                $transaction->save();
            }
        }

        return $journal;
    }
}