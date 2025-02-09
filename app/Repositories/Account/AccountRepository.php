<?php

/**
 * AccountRepository.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Location;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class AccountRepository.
 */
class AccountRepository implements AccountRepositoryInterface
{
    use UserGroupTrait;


    /**
     * Moved here from account CRUD.
     */
    public function destroy(Account $account, ?Account $moveTo): bool
    {
        /** @var AccountDestroyService $service */
        $service = app(AccountDestroyService::class);
        $service->destroy($account, $moveTo);

        return true;
    }

    /**
     * Find account with same name OR same IBAN or both, but not the same type or ID.
     */
    public function expandWithDoubles(Collection $accounts): Collection
    {
        $result = new Collection();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $byName = $this->user->accounts()->where('name', $account->name)
                ->where('id', '!=', $account->id)->first()
            ;
            if (null !== $byName) {
                $result->push($account);
                $result->push($byName);

                continue;
            }
            if (null !== $account->iban) {
                $byIban = $this->user->accounts()->where('iban', $account->iban)
                    ->where('id', '!=', $account->id)->first()
                ;
                if (null !== $byIban) {
                    $result->push($account);
                    $result->push($byIban);

                    continue;
                }
            }
            $result->push($account);
        }

        return $result;
    }

    public function findByAccountNumber(string $number, array $types): ?Account
    {
        $dbQuery = $this->user
            ->accounts()
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('accounts.active', true)
            ->where(
                static function (EloquentBuilder $q1) use ($number): void {
                    $json = json_encode($number);
                    $q1->where('account_meta.name', '=', 'account_number');
                    $q1->where('account_meta.data', '=', $json);
                }
            )
        ;

        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        /** @var null|Account */
        return $dbQuery->first(['accounts.*']);
    }

    public function findByIbanNull(string $iban, array $types): ?Account
    {
        $iban  = Steam::filterSpaces($iban);
        $query = $this->user->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (0 !== count($types)) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        /** @var null|Account */
        return $query->where('iban', $iban)->first(['accounts.*']);
    }

    public function findByName(string $name, array $types): ?Account
    {
        $query   = $this->user->accounts();

        if (0 !== count($types)) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }
        app('log')->debug(sprintf('Searching for account named "%s" (of user #%d) of the following type(s)', $name, $this->user->id), ['types' => $types]);

        $query->where('accounts.name', $name);

        /** @var null|Account $account */
        $account = $query->first(['accounts.*']);
        if (null === $account) {
            app('log')->debug(sprintf('There is no account with name "%s" of types', $name), $types);

            return null;
        }
        app('log')->debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

        return $account;
    }

    /**
     * Return account type or null if not found.
     */
    public function getAccountTypeByType(string $type): ?AccountType
    {
        return AccountType::whereType(ucfirst($type))->first();
    }

    public function getAccountsById(array $accountIds): Collection
    {
        $query = $this->user->accounts();

        if (0 !== count($accountIds)) {
            $query->whereIn('accounts.id', $accountIds);
        }
        $query->orderBy('accounts.order', 'ASC');
        $query->orderBy('accounts.active', 'DESC');
        $query->orderBy('accounts.name', 'ASC');

        return $query->get(['accounts.*']);
    }

    public function getActiveAccountsByType(array $types): Collection
    {
        $query = $this->user->accounts()->with(
            [  // @phpstan-ignore-line
                'accountmeta' => static function (HasMany $query): void {
                    $query->where('name', 'account_role');
                },
                'attachments',
            ]
        );
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        $query->where('active', true);
        $query->orderBy('accounts.account_type_id', 'ASC');
        $query->orderBy('accounts.order', 'ASC');
        $query->orderBy('accounts.name', 'ASC');

        return $query->get(['accounts.*']);
    }

    public function getAttachments(Account $account): Collection
    {
        $set  = $account->attachments()->get();

        /** @var \Storage $disk */
        $disk = \Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) { // @phpstan-ignore-line
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes_text  = null !== $notes ? $notes->text : '';

                return $attachment;
            }
        );
    }

    /**
     * @throws FireflyException
     */
    public function getCashAccount(): Account
    {
        /** @var AccountType $type */
        $type    = AccountType::where('type', AccountTypeEnum::CASH->value)->first();

        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->findOrCreate('Cash account', $type->type);
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function getCreditTransactionGroup(Account $account): ?TransactionGroup
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])
        ;

        return $journal?->transactionGroup;
    }

    public function getInactiveAccountsByType(array $types): Collection
    {
        $query = $this->user->accounts()->with(
            [ // @phpstan-ignore-line
                'accountmeta' => static function (HasMany $query): void {
                    $query->where('name', 'account_role');
                },
            ]
        );
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        $query->where('active', 0);
        $query->orderBy('accounts.account_type_id', 'ASC');
        $query->orderBy('accounts.order', 'ASC');
        $query->orderBy('accounts.name', 'ASC');

        return $query->get(['accounts.*']);
    }

    public function getLocation(Account $account): ?Location
    {
        /** @var null|Location */
        return $account->locations()->first();
    }

    /**
     * Get note text or null.
     */
    public function getNoteText(Account $account): ?string
    {
        return $account->notes()->first()?->text;
    }

    /**
     * Returns the amount of the opening balance for this account.
     */
    public function getOpeningBalanceAmount(Account $account, bool $convertToNative): ?string
    {
        $journal     = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value, TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])
        ;
        if (null === $journal) {
            return null;
        }
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();
        if (null === $transaction) {
            return null;
        }
        if ($convertToNative) {
            return $transaction->native_amount ?? '0';
        }

        return $transaction->amount;
    }

    /**
     * Return date of opening balance as string or null.
     */
    public function getOpeningBalanceDate(Account $account): ?string
    {
        return TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value, TransactionTypeEnum::LIABILITY_CREDIT->value])
            ->first(['transaction_journals.*'])?->date->format('Y-m-d H:i:s')
        ;
    }

    public function getOpeningBalanceGroup(Account $account): ?TransactionGroup
    {
        $journal = $this->getOpeningBalance($account);

        return $journal?->transactionGroup;
    }

    public function getOpeningBalance(Account $account): ?TransactionJournal
    {
        return TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transactions.account_id', $account->id)
            ->transactionTypes([TransactionTypeEnum::OPENING_BALANCE->value])
            ->first(['transaction_journals.*'])
        ;
    }

    public function getPiggyBanks(Account $account): Collection
    {
        return $account->piggyBanks()->get();
    }

    /**
     * @throws FireflyException
     */
    public function getReconciliation(Account $account): ?Account
    {
        if (AccountTypeEnum::ASSET->value !== $account->accountType->type) {
            throw new FireflyException(sprintf('%s is not an asset account.', $account->name));
        }
        $currency = $this->getAccountCurrency($account) ?? app('amount')->getNativeCurrency();
        $name     = trans('firefly.reconciliation_account_name', ['name' => $account->name, 'currency' => $currency->code]);

        /** @var AccountType $type */
        $type     = AccountType::where('type', AccountTypeEnum::RECONCILIATION->value)->first();

        /** @var null|Account $current */
        $current  = $this->user->accounts()->where('account_type_id', $type->id)
            ->where('name', $name)
            ->first()
        ;

        if (null !== $current) {
            return $current;
        }

        $data     = [
            'account_type_id'   => null,
            'account_type_name' => AccountTypeEnum::RECONCILIATION->value,
            'active'            => true,
            'name'              => $name,
            'currency_id'       => $currency->id,
            'currency_code'     => $currency->code,
        ];

        /** @var AccountFactory $factory */
        $factory  = app(AccountFactory::class);
        $factory->setUser($account->user);

        return $factory->create($data);
    }

    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $type       = $account->accountType->type;
        $list       = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $currencyId = (int) $this->getMetaValue($account, 'currency_id');
        if ($currencyId > 0) {
            return TransactionCurrency::find($currencyId);
        }

        return null;
    }

    /**
     * Return meta value for account. Null if not found.
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
        $result = $account->accountMeta->filter(
            static function (AccountMeta $meta) use ($field) {
                return strtolower($meta->name) === strtolower($field);
            }
        );
        if (0 === $result->count()) {
            return null;
        }
        if (1 === $result->count()) {
            return (string) $result->first()->data;
        }

        return null;
    }

    public function count(array $types): int
    {
        return $this->user->accounts()->accountTypeIn($types)->count();
    }

    public function find(int $accountId): ?Account
    {
        /** @var null|Account */
        return $this->user->accounts()->find($accountId);
    }

    public function getUsedCurrencies(Account $account): Collection
    {
        $info        = $account->transactions()->distinct()->groupBy('transaction_currency_id')->get(['transaction_currency_id'])->toArray();
        $currencyIds = [];
        foreach ($info as $entry) {
            $currencyIds[] = (int) $entry['transaction_currency_id'];
        }
        $currencyIds = array_unique($currencyIds);

        return TransactionCurrency::whereIn('id', $currencyIds)->get();
    }

    public function isLiability(Account $account): bool
    {
        return in_array($account->accountType->type, [AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value], true);
    }

    public function maxOrder(string $type): int
    {
        $sets     = [
            AccountTypeEnum::ASSET->value    => [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value],
            AccountTypeEnum::EXPENSE->value  => [AccountTypeEnum::EXPENSE->value, AccountTypeEnum::BENEFICIARY->value],
            AccountTypeEnum::REVENUE->value  => [AccountTypeEnum::REVENUE->value],
            AccountTypeEnum::LOAN->value     => [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::MORTGAGE->value],
            AccountTypeEnum::DEBT->value     => [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::MORTGAGE->value],
            AccountTypeEnum::MORTGAGE->value => [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::MORTGAGE->value],
        ];
        if (array_key_exists(ucfirst($type), $sets)) {
            $order = (int) $this->getAccountsByType($sets[ucfirst($type)])->max('order');
            app('log')->debug(sprintf('Return max order of "%s" set: %d', $type, $order));

            return $order;
        }
        $specials = [AccountTypeEnum::CASH->value, AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::RECONCILIATION->value];

        $order    = (int) $this->getAccountsByType($specials)->max('order');
        app('log')->debug(sprintf('Return max order of "%s" set (specials!): %d', $type, $order));

        return $order;
    }

    public function getAccountsByType(array $types, ?array $sort = []): Collection
    {
        $res   = array_intersect([AccountTypeEnum::ASSET->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value], $types);
        $query = $this->user->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        // add sort parameters. At this point they're filtered to allowed fields to sort by:
        if (0 !== count($sort)) {
            foreach ($sort as $param) {
                $query->orderBy($param[0], $param[1]);
            }
        }

        if (0 === count($sort)) {
            if (0 !== count($res)) {
                $query->orderBy('accounts.order', 'ASC');
            }
            $query->orderBy('accounts.active', 'DESC');
            $query->orderBy('accounts.name', 'ASC');
        }

        return $query->get(['accounts.*']);
    }

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournalDate(Account $account): ?Carbon
    {
        $journal = $this->oldestJournal($account);

        return $journal?->date;
    }

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournal(Account $account): ?TransactionJournal
    {
        /** @var null|TransactionJournal $first */
        $first = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'ASC')
            ->orderBy('transaction_journals.order', 'DESC')
            ->where('transaction_journals.user_id', $this->user->id)
            ->orderBy('transaction_journals.id', 'ASC')
            ->first(['transaction_journals.id'])
        ;
        if (null !== $first) {
            /** @var null|TransactionJournal */
            return TransactionJournal::find($first->id);
        }

        return null;
    }

    public function resetAccountOrder(): void
    {
        $sets = [
            [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value],
            // [AccountTypeEnum::EXPENSE->value, AccountTypeEnum::BENEFICIARY->value],
            // [AccountTypeEnum::REVENUE->value],
            [AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::MORTGAGE->value],
            // [AccountTypeEnum::CASH->value, AccountTypeEnum::INITIAL_BALANCE->value, AccountTypeEnum::IMPORT->value, AccountTypeEnum::RECONCILIATION->value],
        ];
        foreach ($sets as $set) {
            $list  = $this->getAccountsByType($set);
            $index = 1;
            foreach ($list as $account) {
                if (false === $account->active) {
                    $account->order = 0;

                    continue;
                }
                if ($index !== (int) $account->order) {
                    app('log')->debug(sprintf('Account #%d ("%s"): order should %d be but is %d.', $account->id, $account->name, $index, $account->order));
                    $account->order = $index;
                    $account->save();
                }
                ++$index;
            }
        }
        // reset the rest to zero.
        $all  = [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::CREDITCARD->value, AccountTypeEnum::MORTGAGE->value];
        $this->user->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->whereNotIn('account_types.type', $all)
            ->update(['order' => 0])
        ;
    }

    /**
     * @throws FireflyException
     */
    public function update(Account $account, array $data): Account
    {
        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);

        return $service->update($account, $data);
    }

    public function searchAccount(string $query, array $types, int $limit): Collection
    {
        $dbQuery = $this->user->accounts()
            ->where('active', true)
            ->orderBy('accounts.order', 'ASC')
            ->orderBy('accounts.account_type_id', 'ASC')
            ->orderBy('accounts.name', 'ASC')
            ->with(['accountType'])
        ;
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->whereLike('name', $search);
            }
        }
        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }

    public function searchAccountNr(string $query, array $types, int $limit): Collection
    {
        $dbQuery = $this->user->accounts()->distinct()
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('accounts.active', true)
            ->orderBy('accounts.order', 'ASC')
            ->orderBy('accounts.account_type_id', 'ASC')
            ->orderBy('accounts.name', 'ASC')
            ->with(['accountType', 'accountMeta'])
        ;
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where(
                    static function (EloquentBuilder $q1) use ($search): void {
                        $q1->whereLike('accounts.iban', $search);
                        $q1->orWhere(
                            static function (EloquentBuilder $q2) use ($search): void {
                                $q2->where('account_meta.name', '=', 'account_number');
                                $q2->whereLike('account_meta.data', $search);
                            }
                        );
                    }
                );
            }
        }
        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Account
    {
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }
}
