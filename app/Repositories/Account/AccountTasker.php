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
use FireflyIII\Models\Transaction;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;
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
     * @return array
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $ids       = $accounts->pluck('id')->toArray();
        $yesterday = clone $start;
        $yesterday->subDay();
        $startSet = Steam::balancesById($ids, $yesterday);
        $endSet   = Steam::balancesById($ids, $end);

        Log::debug('Start of accountreport');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $return = [
            'start'      => '0',
            'end'        => '0',
            'difference' => '0',
            'accounts'   => [],
        ];

        foreach ($accounts as $account) {
            $id    = $account->id;
            $entry = [
                'name'          => $account->name,
                'id'            => $account->id,
                'start_balance' => '0',
                'end_balance'   => '0',
            ];

            // get first journal date:
            $first                  = $repository->oldestJournal($account);
            $entry['start_balance'] = $startSet[$account->id] ?? '0';
            $entry['end_balance']   = $endSet[$account->id] ?? '0';
            if (!is_null($first->id) && $yesterday < $first->date && $end >= $first->date) {
                // something about balance?
                $entry['start_balance'] = $first->transactions()->where('account_id', $account->id)->first()->amount;
                Log::debug(sprintf('Account was opened before %s, so opening balance is %f', $yesterday->format('Y-m-d'), $entry['start_balance']));
            }
            $return['start'] = bcadd($return['start'], $entry['start_balance']);
            $return['end']   = bcadd($return['end'], $entry['end_balance']);

            $return['accounts'][$id] = $entry;
        }

        $return['difference'] = bcsub($return['end'], $return['start']);

        return $return;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
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
