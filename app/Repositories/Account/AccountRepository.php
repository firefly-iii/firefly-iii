<?php
/**
 * AccountRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
use Illuminate\Database\Eloquent\Builder;
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
     * Moved here from account CRUD
     *
     * @param array $types
     *
     * @return int
     */
    public function count(array $types):int
    {
        $count = $this->user->accounts()->accountTypeIn($types)->count();

        return $count;
    }

    /**
     * Moved here from account CRUD.
     *
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
            $query->where(
            // source.account_id in accountIds XOR destination.account_id in accountIds
                function (Builder $query) use ($accountIds) {
                    $query->where(
                        function (Builder $q1) use ($accountIds) {
                            $q1->whereIn('source.account_id', $accountIds)
                               ->whereNotIn('destination.account_id', $accountIds);
                        }
                    )->orWhere(
                        function (Builder $q2) use ($accountIds) {
                            $q2->whereIn('destination.account_id', $accountIds)
                               ->whereNotIn('source.account_id', $accountIds);
                        }
                    );
                }
            );
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
}
