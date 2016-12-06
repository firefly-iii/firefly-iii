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
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return AccountCollection
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): AccountCollection
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

        $query = Transaction::distinct()
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

}
