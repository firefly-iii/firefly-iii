<?php

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;

class SetLatestVersion extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:set-latest-version {--james-is-cool}';

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
    public function handle()
    {
        if (!$this->option('james-is-cool')) {
            $this->error('Am too!');

            return;
        }
        app('fireflyconfig')->set('db_version', config('firefly.db_version'));
        app('fireflyconfig')->set('ff3_version', config('firefly.version'));
        $this->line('Updated version.');

        return 0;
    }
}
