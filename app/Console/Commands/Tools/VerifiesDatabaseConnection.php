<?php




declare(strict_types=1);



namespace FireflyIII\Console\Commands\Tools;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class VerifiesDatabaseConnection extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesDatabaseConnectionTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'firefly-iii:verify-database-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command tries to connect to the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connected = $this->verifyDatabaseConnection();
        if ($connected) {
            $this->friendlyPositive('Connected to the database.');

            return Command::SUCCESS;
        }
        $this->friendlyError('Failed to connect to the database. Is it up?');

        return Command::FAILURE;
    }
}
