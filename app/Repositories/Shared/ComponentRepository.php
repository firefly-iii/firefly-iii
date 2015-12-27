<?php

namespace FireflyIII\Repositories\Shared;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class ComponentRepository
 *
 * @package FireflyIII\Repositories\Shared
 */
class ComponentRepository
{

    /**
     * @param            $object
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    protected function commonBalanceInPeriod($object, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties; // we must cache this.
        $cache->addProperty($object->id);
        $cache->addProperty(get_class($object));
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty('balanceInPeriodList');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $ids = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $ids[] = $account->id;
        }



        $entry  = $object->transactionjournals()
                         ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
                         ->before($end)
                         ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                         ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                         ->whereIn('accounts.id', $ids)
                         ->after($start)
                         ->first([DB::Raw('SUM(`transactions`.`amount`) as `journalAmount`')]);
        $amount = $entry->journalAmount;

        $cache->store($amount);

        return $amount;
    }
}
