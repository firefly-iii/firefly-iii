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

declare(strict_types=1);

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

            // first journal exists, and is on start, then this is the actual opening balance:
            if (!is_null($first->id) && $first->date->isSameDay($start)) {
                Log::debug(sprintf('Date of first journal for %s is %s', $account->name, $first->date->format('Y-m-d')));
                $entry['start_balance'] = $first->transactions()->where('account_id', $account->id)->first()->amount;
                Log::debug(sprintf('Account %s was opened on %s, so opening balance is %f', $account->name, $start->format('Y-m-d'), $entry['start_balance']));
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

}
