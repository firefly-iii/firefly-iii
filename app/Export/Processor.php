<?php
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
        $journalCollector = app('FireflyIII\Export\JournalCollector', $args);
        $this->journals   = $journalCollector->collect();
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
        /** @var TransactionJournal $journal */
        foreach ($this->journals as $journal) {
            $this->exportEntries->push(Entry::fromJournal($journal));
        }
    }

    public function createConfigFile()
    {
        $this->configurationMaker = app('FireflyIII\Export\ConfigurationFile', [$this->job]);
        $this->files->push($this->configurationMaker->make());
    }

    public function createZipFile()
    {
        $zip      = new ZipArchive;
        $filename = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '.zip';

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            throw new FireflyException('Cannot store zip file.');
        }
        // for each file in the collection, add it to the zip file.
        $search = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-';
        /** @var string $file */
        foreach ($this->getFiles() as $file) {
            $zipName = str_replace($search, '', $file);
            $zip->addFile($file, $zipName);
        }
        $zip->close();
    }

    /**
     *
     */
    public function exportJournals()
    {
        $exporterClass = Config::get('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass, [$this->job]);
        $exporter->setEntries($this->exportEntries);
        $exporter->run();
        $this->files->push($exporter->getFileName());
    }

    /**
     * @return Collection
     */
    public function getFiles()
    {
        return $this->files;
    }
}