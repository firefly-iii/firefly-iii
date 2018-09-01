<?php

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use Illuminate\Console\Command;

/**
 * Class Cron
 *
 * @codeCoverageIgnore
 */
class Cron extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all Firefly III cron-job related commands. Configure a cron job according to the official Firefly III documentation.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $recurring = new RecurringCronjob;
        try {
            $result = $recurring->fire();
        } catch (FireflyException $e) {
            $this->error($e->getMessage());

            return 0;
        }
        if (false === $result) {
            $this->line('The recurring transaction cron job did not fire.');
        }
        if (true === $result) {
            $this->line('The recurring transaction cron job fired successfully.');
        }

        $this->info('More feedback on the cron jobs can be found in the log files.');

        return 0;
    }


}
