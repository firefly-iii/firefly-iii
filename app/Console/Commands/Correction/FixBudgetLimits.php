<?php

namespace FireflyIII\Console\Commands\Correction;

use DB;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;

/**
 * Class CorrectionSkeleton
 */
class FixBudgetLimits extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes negative budget limits';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-negative-limits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $set = BudgetLimit::where('amount', '<', '0')->get();
        if (0 === $set->count()) {
            $this->info('All budget limits are OK.');
            return 0;
        }
        $count = BudgetLimit::where('amount', '<', '0')->update(['amount' => DB::raw('amount * -1')]);

        $this->info(sprintf('Fixed %d budget limit(s)', $count));

        return 0;
    }
}
