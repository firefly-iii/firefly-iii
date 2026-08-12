<?php




declare(strict_types=1);



namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Transaction;
use Illuminate\Console\Command;

class ClearsEmptyForeignAmounts extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:clears-empty-foreign-amounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes references to foreign amounts if there is no amount.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // transaction: has no amount, but reference to currency.
        $count = Transaction::query()->whereNull('foreign_amount')->whereNotNull('foreign_currency_id')->count();
        if ($count > 0) {
            Transaction::query()->whereNull('foreign_amount')->whereNotNull('foreign_currency_id')->update(['foreign_currency_id' => null]);
            $this->friendlyInfo(sprintf('Corrected %d invalid foreign amount reference(s)', $count));
        }
        // transaction: has amount, but no currency.
        $count = Transaction::query()->whereNull('foreign_currency_id')->whereNotNull('foreign_amount')->count();
        if ($count > 0) {
            Transaction::query()->whereNull('foreign_currency_id')->whereNotNull('foreign_amount')->update(['foreign_amount' => null]);
            $this->friendlyInfo(sprintf('Corrected %d invalid foreign amount reference(s)', $count));
        }

        return self::SUCCESS;
    }
}
