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
use FireflyIII\Models\Account;
use FireflyIII\User;
use Illuminate\Support\Collection;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use Log;
use Steam;

/**
 * Class AccountTasker
 *
 * @package FireflyIII\Repositories\Account
 */
class AccountTasker
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
}