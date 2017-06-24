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


use Carbon\Carbon;
use FireflyIII\Import\FileProcessor\FileProcessorInterface;
use FireflyIII\Import\Storage\ImportStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

class ImportRoutine
{

    /** @var  Collection */
    public $errors;
    /** @var  Collection */
    public $journals;
    /** @var int */
    public $lines = 0;
    /** @var  ImportJob */
    private $job;

    /**
     * ImportRoutine constructor.
     *
     * @param ImportJob $job
     */
    public function __construct(ImportJob $job)
    {
        $this->job      = $job;
        $this->journals = new Collection;
        $this->errors   = new Collection;
        Log::debug(sprintf('Job ID is #%d', $job->id));
    }

    /**
     *
     */
    public function run(): bool
    {
        if ($this->job->status !== 'configured') {
            Log::error(sprintf('Job %s is in state "%s" so it cannot be started.', $this->job->key, $this->job->status));

            return false;
        }
        set_time_limit(0);
        Log::debug(sprintf('Start with import job %s', $this->job->key));

        $importObjects = $this->getImportObjects();
        $this->lines   = $importObjects->count();

        // once done, use storage thing to actually store them:
        Log::debug(sprintf('Returned %d valid objects from file processor', $this->lines));

        $storage = $this->storeObjects($importObjects);

        // update job:
        $this->job->status = 'finished';
        $this->job->save();

        $this->journals = $storage->journals;
        $this->errors   = $storage->errors;

        // create tag, link tag to all journals:
        $this->createImportTag();

        Log::debug(sprintf('Done with import job %s', $this->job->key));

        return true;
    }

    /**
     * @return Collection
     */
    protected function getImportObjects(): Collection
    {
        $objects = new Collection;
        $type    = $this->job->file_type;
        $class   = config(sprintf('firefly.import_processors.%s', $type));
        /** @var FileProcessorInterface $processor */
        $processor = app($class);
        $processor->setJob($this->job);

        if ($this->job->status == 'configured') {

            // set job as "running"...
            $this->job->status = 'running';
            $this->job->save();

            Log::debug('Job is configured, start with run()');
            $processor->run();
            $objects = $processor->getObjects();
        }

        return $objects;
    }

    /**
     *
     */
    private function createImportTag(): Tag
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($this->job->user);
        $data                       = [
            'tag'         => trans('firefly.import_with_key', ['key' => $this->job->key]),
            'date'        => new Carbon,
            'description' => null,
            'latitude'    => null,
            'longitude'   => null,
            'zoomLevel'   => null,
            'tagMode'     => 'nothing',
        ];
        $tag                        = $repository->store($data);
        $extended                   = $this->job->extended_status;
        $extended['tag']            = $tag->id;
        $this->job->extended_status = $extended;
        $this->job->save();

        $this->journals->each(
            function (TransactionJournal $journal) use ($tag) {
                $journal->tags()->save($tag);
            }
        );

        return $tag;

    }

    /**
     * @param Collection $objects
     *
     * @return ImportStorage
     */
    private function storeObjects(Collection $objects): ImportStorage
    {
        $storage = new ImportStorage;
        $storage->setJob($this->job);
        $storage->setDateFormat($this->job->configuration['date-format']);
        $storage->setObjects($objects);
        $storage->store();

        return $storage;
    }
}