<?php




declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectsInvertedBudgetLimits extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:corrects-inverted-budget-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse budget limits where the dates are inverted.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $set = BudgetLimit::query()->where('start_date', '>', DB::raw('end_date'))->get();
        if (0 === $set->count()) {
            Log::debug('No inverted budget limits found.');

            return Command::SUCCESS;
        }

        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $start                   = $budgetLimit->start_date->copy();
            $end                     = $budgetLimit->end_date->copy();
            $budgetLimit->start_date = $end;
            $budgetLimit->end_date   = $start;
            $budgetLimit->saveQuietly();
        }

        if (1 === $set->count()) {
            $this->friendlyInfo('Corrected one budget limit to have the right start/end dates.');

            return Command::SUCCESS;
        }
        $this->friendlyInfo(sprintf('Corrected %d budget limits to have the right start/end dates.', count($set)));

        return Command::SUCCESS;
    }
}
