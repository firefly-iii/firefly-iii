<?php

namespace FireflyIII\Console\Commands\Correction;

use DB;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\Transaction;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;
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
        AccountBalanceCalculator::recalculate(null);
    }
}
