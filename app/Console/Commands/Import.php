<?php
/**
 * Import.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Import\ImportProcedure;
use FireflyIII\Import\Logging\CommandHandler;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Log;

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
    protected $description = 'This will start a new import.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:start-import {key}';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function handle()
    {
        Log::debug('Start start-import command');
        $jobKey = $this->argument('key');
        $job    = ImportJob::whereKey($jobKey)->first();
        if (!$this->isValid($job)) {
            Log::error('Job is not valid for some reason. Exit.');

            return;
        }

        $this->line(sprintf('Going to import job with key "%s" of type "%s"', $job->key, $job->file_type));

        $monolog = Log::getMonolog();
        $handler = new CommandHandler($this);
        $monolog->pushHandler($handler);

        $result = ImportProcedure::runImport($job);


        /**
         * @var int                $index
         * @var TransactionJournal $journal
         */
        foreach ($result as $index => $journal) {
            if (!is_null($journal->id)) {
                $this->line(sprintf('Line #%d has been imported as transaction #%d.', $index, $journal->id));
                continue;
            }
            $this->error(sprintf('Could not store line #%d', $index));
        }

        $this->line('The import has completed.');

        // get any errors from the importer:
        $extendedStatus = $job->extended_status;
        if (isset($extendedStatus['errors']) && count($extendedStatus['errors']) > 0) {
            $this->line(sprintf('The following %d error(s) occured during the import:', count($extendedStatus['errors'])));
            foreach ($extendedStatus['errors'] as $error) {
                $this->error($error);
            }
        }

        return;
    }

    /**
     * @param ImportJob $job
     *
     * @return bool
     */
    private function isValid(ImportJob $job): bool
    {
        if (is_null($job)) {
            $this->error('This job does not seem to exist.');

            return false;
        }

        if ($job->status != 'settings_complete') {
            $this->error('This job is not ready to be imported.');

            return false;
        }

        return true;
    }
}
