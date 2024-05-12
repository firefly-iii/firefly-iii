<?php

namespace FireflyIII\Console\Commands\Correction;

use DB;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use stdClass;

/**
 * Class CorrectionSkeleton
 */
class CorrectAccountBalance extends Command
{
    protected $description = 'Recalculate all account balance amounts';

    protected $signature = 'firefly-iii:correct-account-balance';
    use ShowsFriendlyMessages;

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
        AccountBalanceCalculator::recalculate(null, null);
        foreach(TransactionJournal::all() as $journal) {
            Log::debug(sprintf('Recalculating account balances for journal #%d', $journal->id));
            foreach($journal->transactions as $transaction) {
                AccountBalanceCalculator::recalculate($transaction->account, $journal);
            }
        }
    }
}
