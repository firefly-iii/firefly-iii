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
use DB;
use FireflyIII\Import\FileProcessor\FileProcessorInterface;
use FireflyIII\Import\Storage\ImportStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
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
     */
    public function __construct()
    {
        $this->journals = new Collection;
        $this->errors   = new Collection;
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
        Log::info(sprintf('Start with import job %s', $this->job->key));

        $importObjects = $this->getImportObjects();
        $this->lines   = $importObjects->count();

        // once done, use storage thing to actually store them:
        Log::info(sprintf('Returned %d valid objects from file processor', $this->lines));

        $storage = $this->storeObjects($importObjects);
        Log::debug('Back in run()');

        // update job:
        $this->job->status = 'finished';
        $this->job->save();

        Log::debug('Updated job...');

        $this->journals = $storage->journals;
        $this->errors   = $storage->errors;

        Log::debug('Going to call createImportTag()');

        // create tag, link tag to all journals:
        $this->createImportTag();

        Log::info(sprintf('Done with import job %s', $this->job->key));


        return true;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
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

        if ($this->job->status === 'configured') {

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
        Log::debug('Now in createImportTag()');
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

        Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
        Log::debug('Looping journals...');
        $journalIds = $this->journals->pluck('id')->toArray();
        $tagId      = $tag->id;
        foreach ($journalIds as $journalId) {
            Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
            DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
        }
        Log::debug('Done!');
        Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $this->journals->count(), $tag->id, $tag->tag));

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
        Log::info('Back in storeObjects()');

        return $storage;
    }
}
