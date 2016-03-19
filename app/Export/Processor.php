<?php
declare(strict_types = 1);
/**
 * Processor.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use Auth;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Log;
use Storage;
use ZipArchive;

/**
 * Class Processor
 *
 * @package FireflyIII\Export
 */
class Processor
{

    /** @var Collection */
    public $accounts;
    /** @var  string */
    public $exportFormat;
    /** @var  bool */
    public $includeAttachments;
    /** @var  bool */
    public $includeConfig;
    /** @var  bool */
    public $includeOldUploads;
    /** @var  ExportJob */
    public $job;
    /** @var array */
    public $settings;
    /** @var  \FireflyIII\Export\ConfigurationFile */
    private $configurationMaker;
    /** @var  Collection */
    private $exportEntries;
    /** @var  Collection */
    private $files;
    /** @var  Collection */
    private $journals;

    /**
     * Processor constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        // save settings
        $this->settings           = $settings;
        $this->accounts           = $settings['accounts'];
        $this->exportFormat       = $settings['exportFormat'];
        $this->includeAttachments = $settings['includeAttachments'];
        $this->includeConfig      = $settings['includeConfig'];
        $this->includeOldUploads  = $settings['includeOldUploads'];
        $this->job                = $settings['job'];
        $this->journals           = new Collection;
        $this->exportEntries      = new Collection;
        $this->files              = new Collection;

    }

    /**
     *
     */
    public function collectAttachments()
    {
        $attachmentCollector = app('FireflyIII\Export\Collector\AttachmentCollector', [$this->job]);
        $attachmentCollector->run();
        $this->files = $this->files->merge($attachmentCollector->getFiles());
    }

    /**
     *
     */
    public function collectJournals()
    {
        $args             = [$this->accounts, Auth::user(), $this->settings['startDate'], $this->settings['endDate']];
        $journalCollector = app('FireflyIII\Repositories\Journal\JournalCollector', $args);
        $this->journals   = $journalCollector->collect();
        Log::debug(
            'Collected ' .
            $this->journals->count() . ' journals (between ' .
            $this->settings['startDate']->format('Y-m-d') . ' and ' .
            $this->settings['endDate']->format('Y-m-d')
            . ').'
        );
    }

    public function collectOldUploads()
    {
        $uploadCollector = app('FireflyIII\Export\Collector\UploadCollector', [$this->job]);
        $uploadCollector->run();

        $this->files = $this->files->merge($uploadCollector->getFiles());
    }

    /**
     *
     */
    public function convertJournals()
    {
        $count = 0;
        /** @var TransactionJournal $journal */
        foreach ($this->journals as $journal) {
            $this->exportEntries->push(Entry::fromJournal($journal));
            $count++;
        }
        Log::debug('Converted ' . $count . ' journals to "Entry" objects.');
    }

    public function createConfigFile()
    {
        $this->configurationMaker = app('FireflyIII\Export\ConfigurationFile', [$this->job]);
        $this->files->push($this->configurationMaker->make());
    }

    public function createZipFile()
    {
        $zip      = new ZipArchive;
        $file     = $this->job->key . '.zip';
        $fullPath = storage_path('export') . '/' . $file;
        Log::debug('Will create zip file at ' . $fullPath);

        if ($zip->open($fullPath, ZipArchive::CREATE) !== true) {
            throw new FireflyException('Cannot store zip file.');
        }
        // for each file in the collection, add it to the zip file.
        $disk = Storage::disk('export');
        foreach ($this->getFiles() as $entry) {
            // is part of this job?
            $zipFileName = str_replace($this->job->key . '-', '', $entry);
            $result      = $zip->addFromString($zipFileName, $disk->get($entry));
            if (!$result) {
                Log::error('Could not add "' . $entry . '" into zip file as "' . $zipFileName . '".');
            }
        }

        $zip->close();

        // delete the files:
        foreach ($this->getFiles() as $file) {
            Log::debug('Will now delete file "' . $file . '".');
            $disk->delete($file);
        }
        Log::debug('Done!');
    }

    /**
     *
     */
    public function exportJournals()
    {
        $exporterClass = Config::get('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass, [$this->job]);
        Log::debug('Going to export ' . $this->exportEntries->count() . ' export entries into ' . $this->exportFormat . ' format.');
        $exporter->setEntries($this->exportEntries);
        $exporter->run();
        $this->files->push($exporter->getFileName());
        Log::debug('Added "' . $exporter->getFileName() . '" to the list of files to include in the zip.');
    }

    /**
     * @return Collection
     */
    public function getFiles()
    {
        return $this->files;
    }
}
