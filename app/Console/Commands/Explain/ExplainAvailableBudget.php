<?php




declare(strict_types=1);



namespace FireflyIII\Console\Commands\Explain;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Console\Command;

class ExplainAvailableBudget extends Command
{
    use VerifiesAccessToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'explain:available-budget
                                {--date=now : A date formatted YYYY-MM-DD or the word "now"}
                                {--user=1 : The user ID.}
                                {--token= : The user\'s access token.}
   ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explains why the available budget amount is what it is.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date  = $this->getDate((string) $this->option('date'));
        $range = Preferences::getForUser($this->getUser(), 'viewRange', '1M')->data ?? '1M';
        $title = Navigation::periodShow($date, $range);
        $this->line('This command explains why the "available" budget bar at the top of your /budget bar means.');
        $this->line(sprintf(
            'You submitted date %s and your settings show a %s period, so this explanation concerns the period "%s".',
            $date->format('Y-m-d'),
            $range,
            $title
        ));

        return Command::SUCCESS;
    }

    private function getDate(string $param): Carbon
    {
        if ('now' === $param) {
            return today();
        }

        try {
            $date = Carbon::parse($param);
        } catch (InvalidFormatException) {
            $this->warn('Invalid date given. Fall back to today\'s date.');

            return today();
        }

        return $date;
    }
}
