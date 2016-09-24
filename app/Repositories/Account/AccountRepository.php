<?php
/**
 * AccountRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
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
     * This method is almost the same as ::earnedInPeriod, but only works for revenue accounts
     * instead of the implied asset accounts for ::earnedInPeriod. ::earnedInPeriod will tell you
     * how much money was earned by the given asset accounts. This method will tell you how much money
     * these given revenue accounts sent. Ie. how much money was made FROM these revenue accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedFromInPeriod(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $query = $this->user->transactionJournals()->expanded()->sortCorrectly()
                            ->transactionTypes([TransactionType::DEPOSIT]);

        if ($end >= $start) {
            $query->before($end)->after($start);
        }

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            );
            $query->whereIn('source.account_id', $accountIds);
            $query->whereNull('source.deleted_at');

        }
        // remove group by
        $query->getQuery()->getQuery()->groups = null;

        // get id's
        $ids = $query->get(['transaction_journals.id'])->pluck('id')->toArray();

        // that should do it:
        $sum = $this->user->transactions()
                          ->whereIn('transaction_journal_id', $ids)
                          ->where('amount', '>', '0')
                          ->whereNull('transactions.deleted_at')
                          ->sum('amount');

        return strval($sum);

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
        $query = $this->user->transactionJournals()->expanded()->sortCorrectly()
                            ->transactionTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);

        if ($end >= $start) {
            $query->before($end)->after($start);
        }

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );
            $query->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            );

            $query->whereIn('destination.account_id', $accountIds);
            $query->whereNotIn('source.account_id', $accountIds);
            $query->whereNull('destination.deleted_at');
            $query->whereNull('source.deleted_at');

        }
        // remove group by
        $query->getQuery()->getQuery()->groups = null;

        // get id's
        $ids = $query->get(['transaction_journals.id'])->pluck('id')->toArray();


        // that should do it:
        $sum = $this->user->transactions()
                          ->whereIn('transaction_journal_id', $ids)
                          ->where('amount', '>', '0')
                          ->whereNull('transactions.deleted_at')
                          ->sum('amount');

        return strval($sum);

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
                    return true;
                }
                /*
                 * The source of a transfer must be one of the $accounts in order to
                 * be included. Otherwise, it would not be an expense.
                 */
                if (in_array($journal->source_account_id, $accountIds)) {
                    return true;
                }

                return false;
            }
        );

        return $journals;
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
                $account->piggyBalance = '0';
                /** @var PiggyBank $piggyBank */
                foreach ($account->piggyBanks as $piggyBank) {
                    $account->piggyBalance = bcadd($account->piggyBalance, $piggyBank->currentRelevantRep()->currentamount);
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

                    $pct                 = $pct > 100 ? 100 : $pct;
                    $account->difference = $diff;
                    $account->percentage = round($pct);

                    return;
                }
                if ($diff >= 0 && $account->startBalance > 0) {
                    $pct                 = ($diff / $account->startBalance) * 100;
                    $pct                 = $pct > 100 ? 100 : $pct;
                    $account->difference = $diff;
                    $account->percentage = round($pct);

                    return;
                }
                $account->difference = $diff;
                $account->percentage = 100;

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
                    return true;
                }
                /*
                 * The destination of a transfer must be one of the $accounts in order to
                 * be included. Otherwise, it would not be income.
                 */
                if (in_array($journal->destination_account_id, $accountIds)) {
                    return true;
                }

                return false;
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
        $query = $this->user->transactionJournals()->expanded()->sortCorrectly();

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
        foreach ($account->piggyBanks()->get() as $p) {
            $currentAmount = $p->currentRelevantRep()->currentamount ?? '0';

            $balance = bcsub($balance, $currentAmount);
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
     * This method is almost the same as ::spentInPeriod, but only works for expense accounts
     * instead of the implied asset accounts for ::spentInPeriod. ::spentInPeriod will tell you
     * how much money was spent by the given asset accounts. This method will tell you how much money
     * these given expense accounts received. Ie. how much money was spent AT these expense accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentAtInPeriod(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var HasMany $query */
        $query = $this->user->transactionJournals()->expanded()->sortCorrectly()
                            ->transactionTypes([TransactionType::WITHDRAWAL]);
        if ($end >= $start) {
            $query->before($end)->after($start);
        }

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );
            $query->whereIn('destination.account_id', $accountIds);

        }
        // remove group by
        $query->getQuery()->getQuery()->groups = null;
        $query->groupBy('aggregate');

        // that should do it:
        $sum = strval($query->sum('destination.amount'));
        if (is_null($sum)) {
            $sum = '0';
        }
        $sum = bcmul($sum, '-1');

        return $sum;
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
        /** @var HasMany $query */
        $query = $this->user->transactionJournals()->expanded()
                            ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        if ($end >= $start) {
            $query->before($end)->after($start);
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
            $query->whereIn('source.account_id', $accountIds);
            $query->whereNotIn('destination.account_id', $accountIds);
            $query->whereNull('source.deleted_at');
            $query->whereNull('destination.deleted_at');
            $query->distinct();

        }
        // remove group by
        $query->getQuery()->getQuery()->groups = null;
        $ids                                   = $query->get(['transaction_journals.id'])->pluck('id')->toArray();

        $sum = $this->user->transactions()
                          ->whereIn('transaction_journal_id', $ids)
                          ->where('amount', '<', '0')
                          ->whereNull('transactions.deleted_at')
                          ->sum('amount');

        return strval($sum);
    }

}
