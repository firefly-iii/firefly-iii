<?php
/**
 * AccountTasker.php
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
use Crypt;
use DB;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;
use stdClass;
use Steam;

/**
 * Class AccountTasker
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountTasker implements AccountTaskerInterface
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
     * @see self::amountInPeriod
     *
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function amountInInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string
    {
        $idList = [
            'accounts' => $accounts->pluck('id')->toArray(),
            'exclude'  => $excluded->pluck('id')->toArray(),
        ];

        Log::debug(
            'Now calling amountInInPeriod.',
            ['accounts' => $idList['accounts'], 'excluded' => $idList['exclude'],
             'start'    => $start->format('Y-m-d'),
             'end'      => $end->format('Y-m-d'),
            ]
        );

        return $this->amountInPeriod($idList, $start, $end, true);

    }

    /**
     * @see self::amountInPeriod
     *
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function amountOutInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string
    {
        $idList = [
            'accounts' => $accounts->pluck('id')->toArray(),
            'exclude'  => $excluded->pluck('id')->toArray(),
        ];

        Log::debug(
            'Now calling amountOutInPeriod.',
            ['accounts' => $idList['accounts'], 'excluded' => $idList['exclude'],
             'start'    => $start->format('Y-m-d'),
             'end'      => $end->format('Y-m-d'),
            ]
        );

        return $this->amountInPeriod($idList, $start, $end, false);

    }

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     * @see self::financialReport
     *
     */
    public function expenseReport(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): Collection
    {
        $idList = [
            'accounts' => $accounts->pluck('id')->toArray(),
            'exclude'  => $excluded->pluck('id')->toArray(),
        ];

        Log::debug(
            'Now calling expenseReport.',
            ['accounts' => $idList['accounts'], 'excluded' => $idList['exclude'],
             'start'    => $start->format('Y-m-d'),
             'end'      => $end->format('Y-m-d'),
            ]
        );

        return $this->financialReport($idList, $start, $end, false);

    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts): AccountCollection
    {
        $startAmount = '0';
        $endAmount   = '0';
        $diff        = '0';
        $ids         = $accounts->pluck('id')->toArray();
        $yesterday   = clone $start;
        $yesterday->subDay();
        $startSet  = Steam::balancesById($ids, $yesterday);
        $backupSet = Steam::balancesById($ids, $start);
        $endSet    = Steam::balancesById($ids, $end);

        Log::debug(
            sprintf(
                'getAccountReport from %s to %s for %d accounts.',
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $accounts->count()
            )
        );
        $accounts->each(
            function (Account $account) use ($startSet, $endSet, $backupSet) {
                $account->startBalance = $startSet[$account->id] ?? '0';
                $account->endBalance   = $endSet[$account->id] ?? '0';

                // check backup set just in case:
                if ($account->startBalance === '0' && isset($backupSet[$account->id])) {
                    $account->startBalance = $backupSet[$account->id];
                }
            }
        );

        // summarize:
        foreach ($accounts as $account) {
            $startAmount = bcadd($startAmount, $account->startBalance);
            $endAmount   = bcadd($endAmount, $account->endBalance);
            $diff        = bcadd($diff, bcsub($account->endBalance, $account->startBalance));
        }

        $object = new AccountCollection;
        $object->setStart($startAmount);
        $object->setEnd($endAmount);
        $object->setDifference($diff);
        $object->setAccounts($accounts);


        return $object;
    }

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::financialReport()
     *
     * @return Collection
     *
     */
    public function incomeReport(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): Collection
    {
        $idList = [
            'accounts' => $accounts->pluck('id')->toArray(),
            'exclude'  => $excluded->pluck('id')->toArray(),
        ];

        Log::debug(
            'Now calling expenseReport.',
            ['accounts' => $idList['accounts'], 'excluded' => $idList['exclude'],
             'start'    => $start->format('Y-m-d'),
             'end'      => $end->format('Y-m-d'),
            ]
        );

        return $this->financialReport($idList, $start, $end, true);
    }

    /**
     * Will return how much money has been going out (ie. spent) by the given account(s).
     * Alternatively, will return how much money has been coming in (ie. earned) by the given accounts.
     *
     * Enter $incoming=true for any money coming in (income)
     * Enter $incoming=false  for any money going out (expenses)
     *
     * This means any money going out or in. You can also submit accounts to exclude,
     * so transfers between accounts are not included.
     *
     * As a general rule:
     *
     * - Asset accounts should return both expenses and earnings. But could return 0.
     * - Expense accounts (where money is spent) should only return earnings (the account gets money).
     * - Revenue accounts (where money comes from) should only return expenses (they spend money).
     *
     *
     *
     * @param array  $accounts
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $incoming
     *
     * @return string
     */
    protected function amountInPeriod(array $accounts, Carbon $start, Carbon $end, bool $incoming): string
    {
        $joinModifier = $incoming ? '<' : '>';
        $selection    = $incoming ? '>' : '<';

        $query = Transaction
            ::distinct()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin(
                'transactions as other_side', function (JoinClause $join) use ($joinModifier) {
                $join->on('transaction_journals.id', '=', 'other_side.transaction_journal_id')->where('other_side.amount', $joinModifier, 0);
            }
            )

            ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
            ->where('transaction_journals.user_id', $this->user->id)
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->whereIn('transactions.account_id', $accounts['accounts'])
            ->where('transactions.amount', $selection, 0);
        if (count($accounts['exclude']) > 0) {
            $query->whereNotIn('other_side.account_id', $accounts['exclude']);
        }

        $result = $query->get(['transactions.id', 'transactions.amount']);
        $sum    = strval($result->sum('amount'));
        if (strlen($sum) === 0) {
            Log::debug('Sum is empty.');
            $sum = '0';
        }
        Log::debug(sprintf('Result is %s', $sum));

        return $sum;
    }

    /**
     *
     * This method will determin how much has flown (in the given period) from OR to $accounts to/from anywhere else,
     * except $excluded. This could be a list of incomes, or a list of expenses. This method shows
     * the name, the amount and the number of transactions. It is a summary, and only used in some reports.
     *
     * $incoming=true a list of incoming money (earnings)
     * $incoming=false a list of outgoing money (expenses).
     *
     * @param array  $accounts
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $incoming
     *
     * Opening balances are ignored.
     *
     * @return Collection
     */
    protected function financialReport(array $accounts, Carbon $start, Carbon $end, bool $incoming): Collection
    {
        $collection   = new Collection;
        $joinModifier = $incoming ? '<' : '>';
        $selection    = $incoming ? '>' : '<';
        $query        = Transaction
            ::distinct()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
            ->leftJoin(
                'transactions as other_side', function (JoinClause $join) use ($joinModifier) {
                $join->on('transaction_journals.id', '=', 'other_side.transaction_journal_id')->where('other_side.amount', $joinModifier, 0);
            }
            )
            ->leftJoin('accounts as other_account', 'other_account.id', '=', 'other_side.account_id')
            ->where('transaction_types.type','!=', TransactionType::OPENING_BALANCE)
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
            ->where('transaction_journals.user_id', $this->user->id)
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->whereIn('transactions.account_id', $accounts['accounts'])
            ->where('other_side.amount', '=', DB::raw('transactions.amount * -1'))
            ->where('transactions.amount', $selection, 0)
            ->orderBy('transactions.amount');

        if (count($accounts['exclude']) > 0) {
            $query->whereNotIn('other_side.account_id', $accounts['exclude']);
        }
        $set = $query->get(
            [
                'transaction_journals.id',
                'other_side.account_id',
                'other_account.name',
                'other_account.encrypted',
                'transactions.amount',
            ]
        );
        // summarize ourselves:
        $temp = [];
        foreach ($set as $entry) {
            // save into $temp:
            $id = intval($entry->account_id);
            if (isset($temp[$id])) {
                $temp[$id]['count']++;
                $temp[$id]['amount'] = bcadd($temp[$id]['amount'], $entry->amount);
            }
            if (!isset($temp[$id])) {
                $temp[$id] = [
                    'name'   => intval($entry->encrypted) === 1 ? Crypt::decrypt($entry->name) : $entry->name,
                    'amount' => $entry->amount,
                    'count'  => 1,
                ];
            }
        }

        // loop $temp and create collection:
        foreach ($temp as $key => $entry) {
            $object         = new stdClass();
            $object->id     = $key;
            $object->name   = $entry['name'];
            $object->count  = $entry['count'];
            $object->amount = $entry['amount'];
            $collection->push($object);
        }

        return $collection;
    }
}
