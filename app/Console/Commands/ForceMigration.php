<?php

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ForceMigration extends Command
{
    use VerifiesAccessToken;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:force-migrations
                            {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will force-run all database migrations.';

    /**
     * Execute the console command.
     * @throws FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }

        $this->error('Running this command is dangerous and can cause data loss.');
        $this->error('Please do not continue.');
        $question = $this->confirm('Do you want to continue?');
        if (true === $question) {
            $user = $this->getUser();
            Log::channel('audit')->info(sprintf('User #%d ("%s") forced migrations.', $user->id, $user->email));
            $this->forceMigration();
            return 0;
        }
        return 0;
    }

    private function forceMigration(): void
    {
        DB::commit();
        $this->line('Dropping "migrations" table...');
        sleep(2);
        Schema::dropIfExists('migrations');
        $this->line('Done!');
        $this->line('Re-run all migrations...');
        Artisan::call('migrate', ['--seed' => true]);
        sleep(2);
        $this->line('');
        $this->info('Done!');
        $this->line('There is a good chance you just saw a lot of error messages.');
        $this->line('No need to panic yet. First try to access Firefly III (again).');
        $this->line('The issue, whatever it was, may have been solved now.');
        $this->line('');
    }
}
