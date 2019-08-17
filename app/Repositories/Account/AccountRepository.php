<?php
/**
 * AccountRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountRepository.
 *
 */
class AccountRepository implements AccountRepositoryInterface
{

    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }
    /**
     * Moved here from account CRUD.
     *
     * @param Account $account
     * @param Account|null $moveTo
     *
     * @return bool
     *

     */
    public function destroy(Account $account, ?Account $moveTo): bool
    {
        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, $moveTo);

        return true;
    }


    /**
     * @param array $types
     *
     * @return int
     */
    public function count(array $types): int
    {
        return $this->user->accounts()->accountTypeIn($types)->count();
    }

    /**
     * @param string $number
     * @param array $types
     *
     * @return Account|null
     */
    public function findByAccountNumber(string $number, array $types): ?Account
    {
        $query = $this->user->accounts()
                            ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                            ->where('account_meta.name', 'account_number')
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

        return null;
    }

    /**
     * @param string $iban
     * @param array $types
     *
     * @return Account|null
     */
    public function findByIbanNull(string $iban, array $types): ?Account
    {
        $query = $this->user->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        // TODO a loop like this is no longer necessary

        $accounts = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->iban === $iban) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $types
     *
     * @return Account|null
     */
    public function findByName(string $name, array $types): ?Account
    {
        $query = $this->user->accounts();

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }
        Log::debug(sprintf('Searching for account named "%s" (of user #%d) of the following type(s)', $name, $this->user->id), ['types' => $types]);

        $accounts = $query->get(['accounts.*']);

        // TODO no longer need to loop like this

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name) {
                Log::debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

                return $account;
            }
        }
        Log::debug(sprintf('There is no account with name "%s" of types', $name), $types);

        return null;
    }

    /**
     * @param int $accountId
     *
     * @return Account|null
     */
    public function findNull(int $accountId): ?Account
    {
        return $this->user->accounts()->find($accountId);
    }

    /**
     * @param Account $account
     *
     * @return TransactionCurrency|null
     */
    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $currencyId = (int)$this->getMetaValue($account, 'currency_id');
        if ($currencyId > 0) {
            return TransactionCurrency::find($currencyId);
        }

        return null;
    }

    /**
     * @param Account $account
     *
     * @return string
     */
    public function getAccountType(Account $account): string
    {
        return $account->accountType->type;
    }

    /**
     * Return account type or null if not found.
     *
     * @param string $type
     *
     * @return AccountType|null
     */
    public function getAccountTypeByType(string $type): ?AccountType
    {
        return AccountType::whereType($type)->first();
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
        $query->orderBy('accounts.active', 'DESC');
        $query->orderBy('accounts.name', 'ASC');

        $result = $query->get(['accounts.*']);

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
        $query->orderBy('accounts.active', 'DESC');
        $query->orderBy('accounts.name', 'ASC');
        $result = $query->get(['accounts.*']);


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
                $query->where('name', 'account_role');
            }]
        );
        if (count($types) > 0) {
            $query->accountTypeIn($types);
        }
        $query->where('active', 1);
        $query->orderBy('accounts.account_type_id', 'ASC');
        $query->orderBy('accounts.name', 'ASC');
        $result = $query->get(['accounts.*']);

        return $result;
    }

    /**
     * @return Account
     *
     * @throws FireflyException
     */
    public function getCashAccount(): Account
    {
        /** @var AccountType $type */
        $type = AccountType::where('type', AccountType::CASH)->first();
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate('Cash account', $type->type);
    }

    /**
     * Return meta value for account. Null if not found.
     *
     * @param Account $account
     * @param string $field
     *
     * @return null|string
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
        // TODO no longer need to loop like this

        foreach ($account->accountMeta as $meta) {
            if ($meta->name === $field) {
                return (string)$meta->data;
            }
        }

        return null;
    }

    /**
     * Get note text or null.
     *
     * @param Account $account
     *
     * @return null|string
     */
    public function getNoteText(Account $account): ?string
    {
        $note = $account->notes()->first();

        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * Returns the amount of the opening balance for this account.
     *
     * @param Account $account
     *
     * @return string
     */
    public function getOpeningBalanceAmount(Account $account): ?string
    {

        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            return null;
        }
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();
        if (null === $transaction) {
            return null;
        }

        return (string)$transaction->amount;
    }

    /**
     * Return date of opening balance as string or null.
     *
     * @param Account $account
     *
     * @return null|string
     */
    public function getOpeningBalanceDate(Account $account): ?string
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            return null;
        }

        return $journal->date->format('Y-m-d');
    }

    /**
     * @param Account $account
     *
     * @return Collection
     */
    public function getPiggyBanks(Account $account): Collection
    {
        return $account->piggyBanks()->get();
    }

    /**
     * @param Account $account
     *
     * @return Account|null
     *
     * @throws FireflyException
     */
    public function getReconciliation(Account $account): ?Account
    {
        if (AccountType::ASSET !== $account->accountType->type) {
            throw new FireflyException(sprintf('%s is not an asset account.', $account->name));
        }
        $name = $account->name . ' reconciliation';
        /** @var AccountType $type */
        $type     = AccountType::where('type', AccountType::RECONCILIATION)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get();

        // TODO no longer need to loop like this

        /** @var Account $current */
        foreach ($accounts as $current) {
            if ($current->name === $name) {
                return $current;
            }
        }
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($account->user);
        $account = $factory->findOrCreate($name, $type->type);

        return $account;
    }

    /**
     * @param Account $account
     *
     * @return bool
     */
    public function isLiability(Account $account): bool
    {
        return in_array($account->accountType->type, [AccountType::CREDITCARD, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE], true);
    }

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function oldestJournal(Account $account): ?TransactionJournal
    {
        $first = $account->transactions()
                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                         ->orderBy('transaction_journals.date', 'ASC')
                         ->orderBy('transaction_journals.order', 'DESC')
                         ->where('transaction_journals.user_id', $this->user->id)
                         ->orderBy('transaction_journals.id', 'ASC')
                         ->first(['transaction_journals.id']);
        if (null !== $first) {
            return TransactionJournal::find((int)$first->id);
        }

        return null;
    }

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon|null
     */
    public function oldestJournalDate(Account $account): ?Carbon
    {
        $result  = null;
        $journal = $this->oldestJournal($account);
        if (null !== $journal) {
            $result = $journal->date;
        }

        return $result;
    }

    /**
     * @param string $query
     * @param array $types
     *
     * @return Collection
     */
    public function searchAccount(string $query, array $types): Collection
    {
        $dbQuery = $this->user->accounts()->with(['accountType']);
        if ('' !== $query) {
            $search = sprintf('%%%s%%', $query);
            $dbQuery->where('name', 'LIKE', $search);
        }
        if (count($types) > 0) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->get(['accounts.*']);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     */
    public function store(array $data): Account
    {
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param Account $account
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     * @throws FireflyException
     * @throws FireflyException
     */
    public function update(Account $account, array $data): Account
    {
        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        return $account;
    }

    /**
     * @param Account $account
     * @return TransactionJournal|null
     */
    public function getOpeningBalance(Account $account): ?TransactionJournal
    {
        return TransactionJournal
            ::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionType::OPENING_BALANCE])
            ->first(['transaction_journals.*']);
    }

    /**
     * @param Account $account
     * @return TransactionGroup|null
     */
    public function getOpeningBalanceGroup(Account $account): ?TransactionGroup
    {
        $journal = $this->getOpeningBalance($account);
        $group   = null;
        if (null !== $journal) {
            $group = $journal->transactionGroup;
        }

        return $group;
    }
}
