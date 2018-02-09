<?php
/**
 * FileRoutine.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
declare(strict_types=1);

namespace FireflyIII\Import\Routine;

use Carbon\Carbon;
use DB;
use FireflyIII\Import\FileProcessor\FileProcessorInterface;
use FireflyIII\Import\Storage\ImportStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class FileRoutine
 */
class FileRoutine implements RoutineInterface
{
    /** @var Collection */
    public $errors;
    /** @var Collection */
    public $journals;
    /** @var int */
    public $lines = 0;
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * ImportRoutine constructor.
     */
    public function __construct()
    {
        $this->journals = new Collection;
        $this->errors   = new Collection;
    }

    /**
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        return $this->journals;
    }

    /**
     * @return int
     */
    public function getLines(): int
    {
        return $this->lines;
    }

    /**
     *
     */
    public function run(): bool
    {
        if ('configured' !== $this->getStatus()) {
            Log::error(sprintf('Job %s is in state "%s" so it cannot be started.', $this->job->key, $this->getStatus()));

            return false;
        }
        set_time_limit(0);
        Log::info(sprintf('Start with import job %s', $this->job->key));

        // total steps: 6
        $this->setTotalSteps(6);

        $importObjects = $this->getImportObjects();
        $this->lines   = $importObjects->count();
        $this->addStep();

        // total steps can now be extended. File has been scanned. 7 steps per line:
        $this->addTotalSteps(7 * $this->lines);

        // once done, use storage thing to actually store them:
        Log::info(sprintf('Returned %d valid objects from file processor', $this->lines));

        $storage = $this->storeObjects($importObjects);
        $this->addStep();
        Log::debug('Back in run()');


        Log::debug('Updated job...');
        Log::debug(sprintf('%d journals in $storage->journals', $storage->journals->count()));
        $this->journals = $storage->journals;
        $this->errors   = $storage->errors;

        Log::debug('Going to call createImportTag()');

        // create tag, link tag to all journals:
        $this->createImportTag();
        $this->addStep();

        // update job:
        $this->setStatus('finished');

        Log::info(sprintf('Done with import job %s', $this->job->key));

        return true;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
    }

    /**
     * @return Collection
     */
    protected function getImportObjects(): Collection
    {
        $objects  = new Collection;
        $fileType = $this->getConfig()['file-type'] ?? 'csv';
        // will only respond to "file"
        $class = config(sprintf('import.options.file.processors.%s', $fileType));
        /** @var FileProcessorInterface $processor */
        $processor = app($class);
        $processor->setJob($this->job);

        if ('configured' === $this->getStatus()) {
            // set job as "running"...
            $this->setStatus('running');

            Log::debug('Job is configured, start with run()');
            $processor->run();
            $objects = $processor->getObjects();
        }

        return $objects;
    }

    /**
     * Shorthand method.
     */
    private function addStep()
    {
        $this->repository->addStepsDone($this->job, 1);
    }

    /**
     * Shorthand
     *
     * @param int $steps
     */
    private function addTotalSteps(int $steps)
    {
        $this->repository->addTotalSteps($this->job, $steps);
    }

    /**
     *
     */
    private function createImportTag(): Tag
    {
        Log::debug('Now in createImportTag()');

        if ($this->journals->count() < 1) {
            Log::info(sprintf('Will not create tag, %d journals imported.', $this->journals->count()));

            return new Tag;
        }
        $this->addTotalSteps($this->journals->count() + 2);

        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($this->job->user);
        $data = [
            'tag'         => trans('import.import_with_key', ['key' => $this->job->key]),
            'date'        => new Carbon,
            'description' => null,
            'latitude'    => null,
            'longitude'   => null,
            'zoomLevel'   => null,
            'tagMode'     => 'nothing',
        ];
        $tag  = $repository->store($data);
        $this->addStep();
        $extended        = $this->getExtendedStatus();
        $extended['tag'] = $tag->id;
        $this->setExtendedStatus($extended);

        Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
        Log::debug('Looping journals...');
        $journalIds = $this->journals->pluck('id')->toArray();
        $tagId      = $tag->id;
        foreach ($journalIds as $journalId) {
            Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
            DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
            $this->addStep();
        }
        Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $this->journals->count(), $tag->id, $tag->tag));
        $this->addStep();

        return $tag;
    }

    /**
     * Shorthand method
     *
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * @return array
     */
    private function getExtendedStatus(): array
    {
        return $this->repository->getExtendedStatus($this->job);
    }

    /**
     * Shorthand method.
     *
     * @return string
     */
    private function getStatus(): string
    {
        return $this->repository->getStatus($this->job);
    }

    /**
     * @param array $extended
     */
    private function setExtendedStatus(array $extended): void
    {
        $this->repository->setExtendedStatus($this->job, $extended);

        return;
    }

    /**
     * Shorthand
     *
     * @param string $status
     */
    private function setStatus(string $status): void
    {
        $this->repository->setStatus($this->job, $status);
    }

    /**
     * Shorthand
     *
     * @param int $steps
     */
    private function setTotalSteps(int $steps)
    {
        $this->repository->setTotalSteps($this->job, $steps);
    }

    /**
     * @param Collection $objects
     *
     * @return ImportStorage
     */
    private function storeObjects(Collection $objects): ImportStorage
    {
        $config  = $this->getConfig();
        $storage = new ImportStorage;
        $storage->setJob($this->job);
        $storage->setDateFormat($config['date-format']);
        $storage->setObjects($objects);
        $storage->store();
        Log::info('Back in storeObjects()');

        return $storage;
    }
}
