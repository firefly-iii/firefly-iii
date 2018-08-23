<?php
/**
 * Import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** @noinspection MultipleReturnStatementsInspection */
/** @noinspection PhpDynamicAsStaticMethodCallInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use Illuminate\Console\Command;
use Log;

/**
 * Class Import.
 *
 * @codeCoverageIgnore
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
     * Run the import routine.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        Log::debug('Start start-import command');
        $jobKey = (string)$this->argument('key');
        /** @var ImportJob $job */
        $job = ImportJob::where('key', $jobKey)->first();
        if (null === $job) {
            $this->errorLine(sprintf('No job found with key "%s"', $jobKey));

            return 1;
        }
        if (!$this->isValid($job)) {
            $this->errorLine('Job is not valid for some reason. Exit.');

            return 1;
        }

        $this->infoLine(sprintf('Going to import job with key "%s" of type "%s"', $job->key, $job->file_type));

        // actually start job:
        $type      = 'csv' === $job->file_type ? 'file' : $job->file_type;
        $key       = sprintf('import.routine.%s', $type);
        $className = config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find import routine class for job of type "%s".', $type)); // @codeCoverageIgnore
        }

        /** @var RoutineInterface $routine */
        $routine = app($className);
        $routine->setImportJob($job);
        $routine->run();

        /**
         * @var int    $index
         * @var string $error
         */
        foreach ($job->errors as $index => $error) {
            $this->errorLine(sprintf('Error importing line #%d: %s', $index, $error));
        }

        /** @var Tag $tag */
        $tag   = $job->tag()->first();
        $count = 0;
        if (null === $tag) {
            $count = $tag->transactionJournals()->count();
        }

        $this->infoLine(sprintf('The import has finished. %d transactions have been imported.', $count));

        return 0;
    }

    /**
     * Displays an error.
     *
     * @param string     $message
     * @param array|null $data
     */
    private function errorLine(string $message, array $data = null): void
    {
        Log::error($message, $data ?? []);
        $this->error($message);

    }

    /**
     * Displays an informational message.
     *
     * @param string $message
     * @param array  $data
     */
    private function infoLine(string $message, array $data = null): void
    {
        Log::info($message, $data ?? []);
        $this->line($message);
    }

    /**
     * Check if job is valid to be imported.
     *
     * @param ImportJob $job
     *
     * @return bool
     */
    private function isValid(ImportJob $job): bool
    {
        if (null === $job) {
            $this->errorLine('This job does not seem to exist.');

            return false;
        }

        if ('configured' !== $job->status) {
            Log::error(sprintf('This job is not ready to be imported (status is %s).', $job->status));
            $this->errorLine('This job is not ready to be imported.');

            return false;
        }

        return true;
    }
}
