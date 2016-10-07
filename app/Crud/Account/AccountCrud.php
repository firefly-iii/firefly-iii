<?php
/**
 * AccountCrud.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Crud\Account;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountCrud
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @package FireflyIII\Crud\Account
 */
class AccountCrud implements AccountCrudInterface
{

    /** @var User */
    private $user;
    /** @var array */
    private $validFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber'];

    /**
     * AccountCrud constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo): bool
    {
        if (!is_null($moveTo->id)) {
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);
        }
        if (!is_null($account)) {
            $account->delete();
        }

        return true;
    }

    /**
     * @param $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account
    {
        $account = $this->user->accounts()->find($accountId);
        if (is_null($account)) {
            return new Account;
        }

        return $account;
    }

    /**
     * @param string $number
     * @param array  $types
     *
     * @return Account
     */
    public function findByAccountNumber(string $number, array $types): Account
    {
        $query = $this->user->accounts()
                            ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                            ->where('account_meta.name', 'accountNumber')
                            ->where('account_meta.data', json_encode($number));

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        /** @var Collection $accounts */
        $accounts = $query->get(['accounts.*']);
        if ($accounts->count() > 0) {
            return $accounts->first();
        }

        return new Account;
    }

    /**
     * @param string $iban
     * @param array  $types
     *
     * @return Account
     */
    public function findByIban(string $iban, array $types): Account
    {
        $query = $this->user->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        $accounts = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->iban === $iban) {
                return $account;
            }
        }

        return new Account;
    }

    /**
     * @param string $name
     * @param array  $types
     *
     * @return Account
     */
    public function findByName(string $name, array $types): Account
    {
        $query = $this->user->accounts();

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);

        }
        Log::debug(sprintf('Searching for account named %s of the following type(s)', $name), ['types' => $types]);

        $accounts = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name) {
                Log::debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

                return $account;
            }
        }
        Log::debug('Found nothing.');

        return new Account;
    }

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts();

        if (count($accountIds) > 0) {
            $query->whereIn('accounts.id', $accountIds);
        }

        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts();
        if (count($types) > 0) {
            $query->accountTypeIn($types);
        }

        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getActiveAccountsByType(array $types): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        );
        if (count($types) > 0) {
            $query->accountTypeIn($types);
        }
        $query->where('active', 1);
        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data): Account
    {
        $newAccount = $this->storeAccount($data);
        if (!is_null($newAccount->id)) {
            $this->storeMetadata($newAccount, $data);
        }

        if ($data['openingBalance'] != 0) {
            $this->storeInitialBalance($newAccount, $data);
        }

        return $newAccount;

    }

    /**
     * @param $account
     * @param $name
     * @param $value
     *
     * @return AccountMeta
     */
    public function storeMeta(Account $account, string $name, $value): AccountMeta
    {
        return AccountMeta::create(['name' => $name, 'data' => $value, 'account_id' => $account->id,]);
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        // update the account:
        $account->name            = $data['name'];
        $account->active          = $data['active'] == '1' ? true : false;
        $account->virtual_balance = $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        $this->updateMetadata($account, $data);
        $this->updateInitialBalance($account, $data);

        return $account;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    protected function storeAccount(array $data): Account
    {
        $type        = config('firefly.accountTypeByIdentifier.' . $data['accountType']);
        $accountType = AccountType::whereType($type)->first();
        $newAccount  = new Account(
            [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtualBalance'],
                'active'          => $data['active'] === true ? true : false,
                'iban'            => $data['iban'],
            ]
        );

        if (!$newAccount->isValid()) {
            // does the account already exist?
            $searchData      = [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'virtual_balance' => $data['virtualBalance'],
                'name'            => $data['name'],
                'iban'            => $data['iban'],
            ];
            $existingAccount = Account::firstOrNullEncrypted($searchData);
            if (!$existingAccount) {
                Log::error('Account create error', $newAccount->getErrors()->toArray());

                return new Account;
            }
            $newAccount = $existingAccount;

        }
        $newAccount->save();

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionJournal
     */
    protected function storeInitialBalance(Account $account, array $data): TransactionJournal
    {
        $amount          = $data['openingBalance'];
        $user            = $data['user'];
        $name            = $data['name'];
        $opposing        = $this->storeOpposingAccount($amount, $user, $name);
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $journal         = TransactionJournal::create(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['openingBalanceCurrency'],
                'description'             => 'Initial balance for "' . $account->name . '"',
                'completed'               => true,
                'date'                    => $data['openingBalanceDate'],
                'encrypted'               => true,
            ]
        );

        $firstAccount  = $account;
        $secondAccount = $opposing;
        $firstAmount   = $amount;
        $secondAmount  = $amount * -1;

        if ($data['openingBalance'] < 0) {
            $firstAccount  = $opposing;
            $secondAccount = $account;
            $firstAmount   = $amount * -1;
            $secondAmount  = $amount;
        }

        $one = new Transaction(['account_id' => $firstAccount->id, 'transaction_journal_id' => $journal->id, 'amount' => $firstAmount]);
        $one->save();// first transaction: from
        $two = new Transaction(['account_id' => $secondAccount->id, 'transaction_journal_id' => $journal->id, 'amount' => $secondAmount]);
        $two->save(); // second transaction: to

        return $journal;
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    protected function storeMetadata(Account $account, array $data)
    {
        foreach ($this->validFields as $field) {
            if (isset($data[$field])) {
                $metaData = new AccountMeta(
                    [
                        'account_id' => $account->id,
                        'name'       => $field,
                        'data'       => $data[$field],
                    ]
                );
                $metaData->save();
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return bool
     */
    protected function updateInitialBalance(Account $account, array $data): bool
    {
        $openingBalance = $this->openingBalanceTransaction($account);
        if ($data['openingBalance'] != 0) {
            if (!is_null($openingBalance->id)) {
                $date   = $data['openingBalanceDate'];
                $amount = $data['openingBalance'];

                return $this->updateJournal($account, $openingBalance, $date, $amount);
            }

            $this->storeInitialBalance($account, $data);

            return true;
        }
        // else, delete it:
        if ($openingBalance) { // opening balance is zero, should we delete it?
            $openingBalance->delete(); // delete existing opening balance.
        }

        return true;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     */
    protected function updateMetadata(Account $account, array $data)
    {
        foreach ($this->validFields as $field) {
            $entry = $account->accountMeta()->where('name', $field)->first();

            if (isset($data[$field])) {
                // update if new data is present:
                if (!is_null($entry)) {
                    $entry->data = $data[$field];
                    $entry->save();

                    continue;
                }
                $metaData = new AccountMeta(
                    [
                        'account_id' => $account->id,
                        'name'       => $field,
                        'data'       => $data[$field],
                    ]
                );
                $metaData->save();
            }
        }

    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    private function openingBalanceTransaction(Account $account): TransactionJournal
    {
        $journal = TransactionJournal
            ::sortCorrectly()
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionType::OPENING_BALANCE])
            ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * @param float  $amount
     * @param int    $user
     * @param string $name
     *
     * @return Account
     */
    private function storeOpposingAccount(float $amount, int $user, string $name):Account
    {
        $type         = $amount < 0 ? 'expense' : 'revenue';
        $opposingData = [
            'user'           => $user,
            'accountType'    => $type,
            'name'           => $name . ' initial balance',
            'active'         => false,
            'iban'           => '',
            'virtualBalance' => 0,
        ];

        return $this->storeAccount($opposingData);
    }

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param Carbon             $date
     * @param float              $amount
     *
     * @return bool
     */
    private function updateJournal(Account $account, TransactionJournal $journal, Carbon $date, float $amount): bool
    {
        // update date:
        $journal->date = $date;
        $journal->save();
        // update transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($account->id == $transaction->account_id) {
                $transaction->amount = $amount;
                $transaction->save();
            }
            if ($account->id != $transaction->account_id) {
                $transaction->amount = $amount * -1;
                $transaction->save();
            }
        }

        return true;

    }
}
