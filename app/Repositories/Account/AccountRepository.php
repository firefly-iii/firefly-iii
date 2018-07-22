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
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\User;

/**
 * Class AccountRepository.
 */
class AccountRepository implements AccountRepositoryInterface
{
    use FindAccountsTrait;
    /** @var User */
    private $user;

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
     * Moved here from account CRUD.
     *
     * @param Account      $account
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
     * @param int $accountId
     *
     * @return Account|null
     */
    public function findNull(int $accountId): ?Account
    {
        return $this->user->accounts()->find($accountId);
    }

    /**
     * Return meta value for account. Null if not found.
     *
     * @param Account $account
     * @param string  $field
     *
     * @return null|string
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
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
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return TransactionJournal
     */
    public function oldestJournal(Account $account): TransactionJournal
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

        return new TransactionJournal();
    }

    /**
     * Returns the date of the very first transaction in this account.
     * TODO refactor to nullable.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function oldestJournalDate(Account $account): Carbon
    {
        $result  = new Carbon;
        $journal = $this->oldestJournal($account);
        if (null !== $journal->id) {
            $result = $journal->date;
        }

        return $result;
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
     * @throws \FireflyIII\Exceptions\FireflyException
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
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        return $account;
    }
}
