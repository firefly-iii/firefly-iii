<?php

namespace FireflyIII\Console\Commands\Correction;

use DB;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use Illuminate\Console\Command;
use stdClass;

/**
 * Class CorrectionSkeleton
 */
class CorrectAccountBalance extends Command
{
    protected $description = 'Recalculate all account balance amounts';

    protected $signature = 'firefly-iii:correct-account-balance';

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->correctBalanceAmounts();


        return 0;
    }

    private function correctBalanceAmounts(): void
    {
        $result = Transaction
            ::groupBy(['account_id', 'transaction_currency_id'])
            ->get(['account_id', 'transaction_currency_id', DB::raw('SUM(amount) as amount_sum')]);
        /** @var stdClass $entry */
        foreach ($result as $entry) {
            $account  = (int) $entry->account_id;
            $currency = (int) $entry->transaction_currency_id;
            $sum      = $entry->amount_sum;

            AccountBalance::updateOrCreate(
                ['account_id' => $account, 'transaction_currency_id' => $currency],
                ['balance' => $sum]
            );
        }
    }
}
