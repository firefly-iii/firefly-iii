<?php

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class CorrectionSkeleton
 */
class CorrectAccountBalance extends Command
{
    use ShowsFriendlyMessages;
    protected $description = 'Recalculate all account balance amounts';

    protected $signature   = 'firefly-iii:correct-account-balance';

    public function handle(): int
    {
        $this->correctBalanceAmounts();

        return 0;
    }

    private function correctBalanceAmounts(): void
    {
        AccountBalanceCalculator::recalculate(null, null);
        foreach (TransactionJournal::all() as $journal) {
            Log::debug(sprintf('Recalculating account balances for journal #%d', $journal->id));
            foreach ($journal->transactions as $transaction) {
                AccountBalanceCalculator::recalculate($transaction->account, $journal);
            }
        }
    }
}
