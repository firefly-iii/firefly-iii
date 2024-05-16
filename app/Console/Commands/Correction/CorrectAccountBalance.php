<?php

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;

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
        AccountBalanceCalculator::recalculateAll();
    }
}
