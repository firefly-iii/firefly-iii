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
use FireflyIII\Exceptions\FireflyException;
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
        $this->updateMetadata($newAccount, $data);

        if ($this->validOpeningBalanceData($data)) {
            $this->updateInitialBalance($newAccount, $data);

            return $newAccount;
        }
        $this->deleteInitialBalance($newAccount);

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
     * @param Account $account
     */
    protected function deleteInitialBalance(Account $account)
    {
        $journal = $this->openingBalanceTransaction($account);
        if (!is_null($journal->id)) {
            $journal->delete();
        }

    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    protected function openingBalanceTransaction(Account $account): TransactionJournal
    {
        $journal = TransactionJournal
            ::sortCorrectly()
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionType::OPENING_BALANCE])
            ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            Log::debug('Could not find a opening balance journal, return empty one.');

            return new TransactionJournal;
        }
        Log::debug(sprintf('Found opening balance: journal #%d.', $journal->id));

        return $journal;
    }

    /**
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     */
    protected function storeAccount(array $data): Account
    {
        $data['accountType'] = $data['accountType'] ?? 'invalid';
        $type                = config('firefly.accountTypeByIdentifier.' . $data['accountType']);
        $accountType         = AccountType::whereType($type)->first();

        // verify account type
        if (is_null($accountType)) {
            throw new FireflyException(sprintf('Account type "%s" is invalid. Cannot create account.', $data['accountType']));
        }

        // account may exist already:
        $existingAccount = $this->findByName($data['name'], [$data['accountType']]);
        if (!is_null($existingAccount->id)) {
            throw new FireflyException(sprintf('There already is an account named "%s" of type "%s".', $data['name'], $data['accountType']));
        }

        // create it:
        $newAccount = new Account(
            [
                'user_id'         => $data['user'],
                'account_type_id' => $accountType->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtualBalance'],
                'active'          => $data['active'] === true ? true : false,
                'iban'            => $data['iban'],
            ]
        );
        $newAccount->save();
        // verify its creation:
        if (is_null($newAccount->id)) {
            Log::error(
                sprintf('Could not create account "%s" (%d error(s))', $data['name'], $newAccount->getErrors()->count()), $newAccount->getErrors()->toArray()
            );
            throw new FireflyException(sprintf('Tried to create account named "%s" but failed. The logs have more details.', $data['name']));
        }

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
        Log::debug(sprintf('Created new opening balance journal: #%d', $journal->id));

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

        Log::debug(sprintf('Stored two transactions, #%d and #%d', $one->id, $two->id));

        return $journal;
    }

    /**
     * @param float  $amount
     * @param int    $user
     * @param string $name
     *
     * @return Account
     */
    protected function storeOpposingAccount(float $amount, int $user, string $name):Account
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
        Log::debug('Going to create an opening balance opposing account');

        return $this->storeAccount($opposingData);
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

        // no opening balance journal? create it:
        if (is_null($openingBalance->id)) {
            Log::debug('No opening balance journal yet, create journal.');
            $this->storeInitialBalance($account, $data);

            return true;
        }
        // opening balance data? update it!
        if (!is_null($openingBalance->id)) {
            $date   = $data['openingBalanceDate'];
            $amount = $data['openingBalance'];

            Log::debug('Opening balance journal found, update journal.');

            $this->updateOpeningBalanceJournal($account, $openingBalance, $date, $amount);

            return true;
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
            /** @var AccountMeta $entry */
            $entry = $account->accountMeta()->where('name', $field)->first();

            // if $data has field and $entry is null, create new one:
            if (isset($data[$field]) && is_null($entry)) {
                Log::debug(
                    sprintf(
                        'Created meta-field "%s":"%s" for account #%d ("%s") ',
                        $field, $data[$field], $account->id, $account->name
                    )
                );
                AccountMeta::create(
                    [
                        'account_id' => $account->id,
                        'name'       => $field,
                        'data'       => $data[$field],
                    ]
                );
            }

            // if $data has field and $entry is not null, update $entry:
            if (isset($data[$field]) && !is_null($entry)) {
                $entry->data = $data[$field];
                $entry->save();
                Log::debug(
                    sprintf(
                        'Updated meta-field "%s":"%s" for account #%d ("%s") ',
                        $field, $data[$field], $account->id, $account->name
                    )
                );
            }
        }

    }

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param Carbon             $date
     * @param float              $amount
     *
     * @return bool
     */
    protected function updateOpeningBalanceJournal(Account $account, TransactionJournal $journal, Carbon $date, float $amount): bool
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
        Log::debug('Updated opening balance journal.');

        return true;

    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function validOpeningBalanceData(array $data): bool
    {
        if (isset($data['openingBalance'])
            && isset($data['openingBalanceDate'])
            && isset($data['openingBalanceCurrency'])
            && bccomp(strval($data['openingBalance']), '0') !== 0
        ) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
    }
}
