<?php

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;
use Steam;


/**
 *
 * Class AccountRepository
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountRepository implements AccountRepositoryInterface
{

    /** @var User */
    private $user;
    /** @var array */
    private $validFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber'];

    /**
     * AttachmentRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $types
     *
     * @return int
     */
    public function countAccounts(array $types): int
    {
        $count = $this->user->accounts()->accountTypeIn($types)->count();

        return $count;
    }

    /**
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo = null): bool
    {
        if (!is_null($moveTo)) {
            // update all transactions:
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);
        }

        $account->delete();

        return true;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $incomes = $this->incomesInPeriod($accounts, $start, $end);
        $sum     = '0';
        foreach ($incomes as $entry) {
            $amount = TransactionJournal::amount($entry);
            $sum    = bcadd($sum, $amount);
        }

        return $sum;

    }

    /**
     * This method will call AccountRepositoryInterface::journalsInPeriod and get all withdrawaks made from the given $accounts,
     * as well as the transfers that move away from those $accounts. This is a slightly sharper selection
     * than made by journalsInPeriod itself.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountRepositoryInterface::journalsInPeriod
     *
     * @return Collection
     */
    public function expensesInPeriod(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $types      = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        $journals   = $this->journalsInPeriod($accounts, $types, $start, $end);
        $accountIds = $accounts->pluck('id')->toArray();

        // filter because some of these journals are still too much.
        $journals = $journals->filter(
            function (TransactionJournal $journal) use ($accountIds) {
                if ($journal->transaction_type_type == TransactionType::WITHDRAWAL) {
                    return $journal;
                }
                /*
                 * The source of a transfer must be one of the $accounts in order to
                 * be included. Otherwise, it would not be an expense.
                 */
                if (in_array($journal->source_account_id, $accountIds)) {
                    return $journal;
                }
            }
        );

        return $journals;
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
            $account = new Account;
        }

        return $account;
    }

    /**
     * @param Account $account
     *
     * @return Carbon
     */
    public function firstUseDate(Account $account): Carbon
    {
        $first = new Carbon('1900-01-01');

        /** @var Transaction $first */
        $date = $account->transactions()
                        ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                        ->orderBy('transaction_journals.date', 'ASC')
                        ->orderBy('transaction_journals.order', 'DESC')
                        ->orderBy('transaction_journals.id', 'ASC')
                        ->first(['transaction_journals.date']);
        if (!is_null($date)) {
            $first = new Carbon($date->date);
        }

        return $first;
    }

    /**
     * Gets all the accounts by ID, for a given set.
     *
     * @param array $ids
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(array $ids): Collection
    {
        return $this->user->accounts()->whereIn('id', $ids)->get(['accounts.*']);
    }

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        );

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
        $query = $this->user->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        );
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
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account): Transaction
    {
        $transaction = $journal->transactions()->where('account_id', $account->id)->first();
        if (is_null($transaction)) {
            $transaction = new Transaction;
        }

        return $transaction;
    }

    /**
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPiggyBankAccounts(Carbon $start, Carbon $end): Collection
    {
        $collection = new Collection(DB::table('piggy_banks')->distinct()->get(['piggy_banks.account_id']));
        $accountIds = $collection->pluck('account_id')->toArray();
        $accounts   = new Collection;
        $accountIds = array_unique($accountIds);
        if (count($accountIds) > 0) {
            $accounts = $this->user->accounts()->whereIn('id', $accountIds)->where('accounts.active', 1)->get();
        }

        $accounts->each(
            function (Account $account) use ($start, $end) {
                $account->startBalance = Steam::balanceIgnoreVirtual($account, $start);
                $account->endBalance   = Steam::balanceIgnoreVirtual($account, $end);
                $account->piggyBalance = 0;
                /** @var PiggyBank $piggyBank */
                foreach ($account->piggyBanks as $piggyBank) {
                    $account->piggyBalance += $piggyBank->currentRelevantRep()->currentamount;
                }
                // sum of piggy bank amounts on this account:
                // diff between endBalance and piggyBalance.
                // then, percentage.
                $difference          = bcsub($account->endBalance, $account->piggyBalance);
                $account->difference = $difference;
                $account->percentage = $difference != 0 && $account->endBalance != 0 ? round((($difference / $account->endBalance) * 100)) : 100;

            }
        );


        return $accounts;

    }

    /**
     * Get savings accounts.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getSavingsAccounts(Carbon $start, Carbon $end): Collection
    {
        $accounts = $this->user->accounts()->accountTypeIn(['Default account', 'Asset account'])->orderBy('accounts.name', 'ASC')
                               ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                               ->where('account_meta.name', 'accountRole')
                               ->where('accounts.active', 1)
                               ->where('account_meta.data', '"savingAsset"')
                               ->get(['accounts.*']);

        $accounts->each(
            function (Account $account) use ($start, $end) {
                $account->startBalance = Steam::balance($account, $start);
                $account->endBalance   = Steam::balance($account, $end);

                // diff (negative when lost, positive when gained)
                $diff = bcsub($account->endBalance, $account->startBalance);

                if ($diff < 0 && $account->startBalance > 0) {
                    // percentage lost compared to start.
                    $pct = (($diff * -1) / $account->startBalance) * 100;
                } else {
                    if ($diff >= 0 && $account->startBalance > 0) {
                        $pct = ($diff / $account->startBalance) * 100;
                    } else {
                        $pct = 100;
                    }
                }
                $pct                 = $pct > 100 ? 100 : $pct;
                $account->difference = $diff;
                $account->percentage = round($pct);

            }
        );


        return $accounts;
    }

    /**
     * This method will call AccountRepositoryInterface::journalsInPeriod and get all deposits made to the given $accounts,
     * as well as the transfers that move away to those $accounts. This is a slightly sharper selection
     * than made by journalsInPeriod itself.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountRepositoryInterface::journalsInPeriod
     *
     * @return Collection
     */
    public function incomesInPeriod(Collection $accounts, Carbon $start, Carbon $end): Collection
    {
        $types      = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $journals   = $this->journalsInPeriod($accounts, $types, $start, $end);
        $accountIds = $accounts->pluck('id')->toArray();

        // filter because some of these journals are still too much.
        $journals = $journals->filter(
            function (TransactionJournal $journal) use ($accountIds) {
                if ($journal->transaction_type_type == TransactionType::DEPOSIT) {
                    return $journal;
                }
                /*
                 * The destination of a transfer must be one of the $accounts in order to
                 * be included. Otherwise, it would not be income.
                 */
                if (in_array($journal->destination_account_id, $accountIds)) {
                    return $journal;
                }
            }
        );

        return $journals;
    }

    /**
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $accounts, array $types, Carbon $start, Carbon $end): Collection
    {
        // first collect actual transaction journals (fairly easy)
        $query = $this->user->transactionjournals()->expanded()->sortCorrectly();

        if ($end >= $start) {
            $query->before($end)->after($start);
        }

        if (count($types) > 0) {
            $query->transactionTypes($types);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            );
            $query->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );
            $set = join(', ', $accountIds);
            $query->whereRaw('(source.account_id in (' . $set . ') XOR destination.account_id in (' . $set . '))');

        }
        // that should do it:
        $fields   = TransactionJournal::queryFields();
        $fields[] = 'source.account_id as source_account_id';
        $fields[] = 'source.amount as source_amount';
        $fields[] = 'destination.account_id as destination_account_id';
        $fields[] = 'destination.amount as destination_amount';
        $complete = $query->get($fields);

        return $complete;
    }

    /**
     *
     * @param Account $account
     * @param Carbon  $date
     *
     * @return string
     */
    public function leftOnAccount(Account $account, Carbon $date): string
    {

        $balance = Steam::balanceIgnoreVirtual($account, $date);
        /** @var PiggyBank $p */
        foreach ($account->piggybanks()->get() as $p) {
            $balance -= $p->currentRelevantRep()->currentamount;
        }

        return $balance;

    }

    /**
     * Returns the date of the very last transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function newestJournalDate(Account $account): Carbon
    {
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::
        leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->sortCorrectly()
                                     ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return new Carbon('1900-01-01');
        }

        return $journal->date;
    }

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function oldestJournalDate(Account $account): Carbon
    {
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::
        leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->orderBy('transaction_journals.date', 'ASC')
                                     ->orderBy('transaction_journals.order', 'DESC')
                                     ->orderBy('transaction_journals.id', 'ÅSC')
                                     ->first(['transaction_journals.*']);
        if (is_null($journal)) {
            return new Carbon('1900-01-01');
        }

        return $journal->date;
    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function openingBalanceTransaction(Account $account): TransactionJournal
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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $incomes = $this->expensesInPeriod($accounts, $start, $end);
        $sum     = '0';
        foreach ($incomes as $entry) {
            $amount = TransactionJournal::amountPositive($entry);
            $sum    = bcadd($sum, $amount);
        }

        return $sum;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data): Account
    {
        $newAccount = $this->storeAccount($data);
        if (!is_null($newAccount)) {
            $this->storeMetadata($newAccount, $data);
        }


        // continue with the opposing account:
        if ($data['openingBalance'] != 0) {
            $opposingData = [
                'user'           => $data['user'],
                'accountType'    => 'initial',
                'virtualBalance' => 0,
                'name'           => $data['name'] . ' initial balance',
                'active'         => false,
                'iban'           => '',
            ];
            $opposing     = $this->storeAccount($opposingData);
            if (!is_null($opposing) && !is_null($newAccount)) {
                $this->storeInitialBalance($newAccount, $opposing, $data);
            }

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
        $openingBalance = $this->openingBalanceTransaction($account);
        if ($data['openingBalance'] != 0) {
            if (!is_null($openingBalance->id)) {
                $this->updateInitialBalance($account, $openingBalance, $data);
            } else {
                $type         = $data['openingBalance'] < 0 ? 'expense' : 'revenue';
                $opposingData = [
                    'user'           => $data['user'],
                    'accountType'    => $type,
                    'name'           => $data['name'] . ' initial balance',
                    'active'         => false,
                    'iban'           => '',
                    'virtualBalance' => 0,
                ];
                $opposing     = $this->storeAccount($opposingData);
                if (!is_null($opposing)) {
                    $this->storeInitialBalance($account, $opposing, $data);
                }
            }

        } else {
            if ($openingBalance) { // opening balance is zero, should we delete it?
                $openingBalance->delete(); // delete existing opening balance.
            }
        }

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
                Log::error('Account create error: ' . $newAccount->getErrors()->toJson());
                abort(500);
            }
            $newAccount = $existingAccount;

        }
        $newAccount->save();

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param Account $opposing
     * @param array   $data
     *
     * @return TransactionJournal
     */
    protected function storeInitialBalance(Account $account, Account $opposing, array $data): TransactionJournal
    {
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $journal         = TransactionJournal::create(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'bill_id'                 => null,
                'transaction_currency_id' => $data['openingBalanceCurrency'],
                'description'             => 'Initial balance for "' . $account->name . '"',
                'completed'               => true,
                'date'                    => $data['openingBalanceDate'],
                'encrypted'               => true,
            ]
        );

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
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    protected function updateInitialBalance(Account $account, TransactionJournal $journal, array $data): TransactionJournal
    {
        $journal->date = $data['openingBalanceDate'];
        $journal->save();

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
                } else {
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

    }
}
