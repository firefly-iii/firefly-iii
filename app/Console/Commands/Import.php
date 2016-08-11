<?php
/**
 * Import.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Crud\Account\AccountCrud;
use FireflyIII\Import\Importer\ImporterInterface;
use FireflyIII\Import\ImportStorage;
use FireflyIII\Import\ImportValidator;
use FireflyIII\Import\Logging\CommandHandler;
use FireflyIII\Models\ImportJob;
use Illuminate\Console\Command;
use Log;
use Monolog\Handler\StreamHandler;

/**
 * Class Import
 *
 * @package FireflyIII\Console\Commands
 */
class Import extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import stuff into Firefly III.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:import {key}';

    /**
     * Create a new command instance.
     *
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
        $jobKey = $this->argument('key');
        $job    = ImportJob::whereKey($jobKey)->first();
        if (is_null($job)) {
            $this->error('This job does not seem to exist.');

            return;
        }

        if ($job->status != 'settings_complete') {
            $this->error('This job is not ready to be imported.');

            return;
        }

        $this->line('Going to import job with key "' . $job->key . '" of type ' . $job->file_type);
        $class = config('firefly.import_formats.' . $job->file_type);

        /** @var ImporterInterface $importer */
        $importer = app($class);
        $importer->setJob($job);
        // intercept logging by importer.
        $monolog = Log::getMonolog();
        $handler = new CommandHandler($this);

        $monolog->pushHandler($handler);

        // create import entries
        $collection = $importer->createImportEntries();

        // validate / clean collection:
        $validator = new ImportValidator($collection);
        $validator->setUser($job->user);
        if ($job->configuration['import-account'] != 0) {
            $repository = app(AccountCrud::class, [$job->user]);
            $validator->setDefaultImportAccount($repository->find($job->configuration['import-account']));
        }

        $cleaned = $validator->clean();

        // then import collection:
        $storage = new ImportStorage($collection);
        $storage->setUser($job->user);

        // and run store routine:
        $storage->store();


        $this->line('Something something import: ' . $jobKey);
    }
}
