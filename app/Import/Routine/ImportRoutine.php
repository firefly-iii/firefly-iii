<?php
/**
 * ImportRoutine.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Routine;


use FireflyIII\Import\FileProcessor\FileProcessorInterface;
use FireflyIII\Import\Storage\ImportStorage;
use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;
use Log;

class ImportRoutine
{

    /** @var  ImportJob */
    private $job;

    /**
     * ImportRoutine constructor.
     *
     * @param ImportJob $job
     */
    public function __construct(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     *
     */
    public function run(): bool
    {
        if ($this->job->status !== 'configured') {
            Log::error(sprintf('Job %s is in state %s so it cannot be started.', $this->job->key, $this->job->status));

            return false;
        }

        Log::debug(sprintf('Start with import job %s', $this->job->key));
        $objects = new Collection;
        $type    = $this->job->file_type;
        $class   = config(sprintf('firefly.import_processors.%s', $type));
        /** @var FileProcessorInterface $processor */
        $processor = app($class);
        $processor->setJob($this->job);
        set_time_limit(0);
        if ($this->job->status == 'configured') {
            Log::debug('Job is configured, start with run()');
            $processor->run();
            $objects = $processor->getObjects();
        }

        $storage = new ImportStorage;
        $storage->setJob($this->job);
        $storage->setDateFormat($this->job->configuration['date-format']);
        $storage->setObjects($objects);
        $storage->store();

        // once done, use storage thing to actually store them:
        Log::debug(sprintf('Returned %d valid objects from file processor', $objects->count()));

        Log::debug(sprintf('Done with import job %s', $this->job->key));

        return true;
    }
}