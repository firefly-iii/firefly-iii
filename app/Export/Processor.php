<?php
/**
 * Processor.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\Collector\AttachmentCollector;
use FireflyIII\Export\Collector\JournalExportCollector;
use FireflyIII\Export\Collector\UploadCollector;
use FireflyIII\Export\Entry\Entry;
use FireflyIII\Models\ExportJob;
use Illuminate\Support\Collection;
use Log;
use Storage;
use ZipArchive;

/**
 * Class Processor
 *
 * @package FireflyIII\Export
 */
class Processor implements ProcessorInterface
{

    /** @var Collection */
    public $accounts;
    /** @var  string */
    public $exportFormat;
    /** @var  bool */
    public $includeAttachments;
    /** @var  bool */
    public $includeOldUploads;
    /** @var  ExportJob */
    public $job;
    /** @var array */
    public $settings;
    /** @var  Collection */
    private $exportEntries;
    /** @var  Collection */
    private $files;
    /** @var  Collection */
    private $journals;

    /**
     * Processor constructor.
     */
    public function __construct()
    {
        $this->journals      = new Collection;
        $this->exportEntries = new Collection;
        $this->files         = new Collection;

    }

    /**
     * @return bool
     */
    public function collectAttachments(): bool
    {
        /** @var AttachmentCollector $attachmentCollector */
        $attachmentCollector = app(AttachmentCollector::class);
        $attachmentCollector->setJob($this->job);
        $attachmentCollector->setDates($this->settings['startDate'], $this->settings['endDate']);
        $attachmentCollector->run();
        $this->files = $this->files->merge($attachmentCollector->getEntries());

        return true;
    }

    /**
     * @return bool
     */
    public function collectJournals(): bool
    {
        /** @var JournalExportCollector $collector */
        $collector = app(JournalExportCollector::class);
        $collector->setJob($this->job);
        $collector->setDates($this->settings['startDate'], $this->settings['endDate']);
        $collector->setAccounts($this->settings['accounts']);
        $collector->run();
        $this->journals = $collector->getEntries();
        Log::debug(sprintf('Count %d journals in collectJournals() ', $this->journals->count()));

        return true;
    }

    /**
     * @return bool
     */
    public function collectOldUploads(): bool
    {
        /** @var UploadCollector $uploadCollector */
        $uploadCollector = app(UploadCollector::class);
        $uploadCollector->setJob($this->job);
        $uploadCollector->run();

        $this->files = $this->files->merge($uploadCollector->getEntries());

        return true;
    }

    /**
     * @return bool
     */
    public function convertJournals(): bool
    {
        $count = 0;
        foreach ($this->journals as $object) {
            $this->exportEntries->push(Entry::fromObject($object));
            $count++;
        }
        Log::debug(sprintf('Count %d entries in exportEntries (convertJournals)', $this->exportEntries->count()));

        return true;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function createZipFile(): bool
    {
        $zip      = new ZipArchive;
        $file     = $this->job->key . '.zip';
        $fullPath = storage_path('export') . '/' . $file;

        if ($zip->open($fullPath, ZipArchive::CREATE) !== true) {
            throw new FireflyException('Cannot store zip file.');
        }
        // for each file in the collection, add it to the zip file.
        $disk = Storage::disk('export');
        foreach ($this->getFiles() as $entry) {
            // is part of this job?
            $zipFileName = str_replace($this->job->key . '-', '', $entry);
            $zip->addFromString($zipFileName, $disk->get($entry));
        }

        $zip->close();

        // delete the files:
        $this->deleteFiles();

        return true;
    }

    /**
     * @return bool
     */
    public function exportJournals(): bool
    {
        $exporterClass = config('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass);
        $exporter->setJob($this->job);
        $exporter->setEntries($this->exportEntries);
        $exporter->run();
        $this->files->push($exporter->getFileName());

        return true;
    }

    /**
     * @return Collection
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        // save settings
        $this->settings           = $settings;
        $this->accounts           = $settings['accounts'];
        $this->exportFormat       = $settings['exportFormat'];
        $this->includeAttachments = $settings['includeAttachments'];
        $this->includeOldUploads  = $settings['includeOldUploads'];
        $this->job                = $settings['job'];
    }

    /**
     *
     */
    private function deleteFiles()
    {
        $disk = Storage::disk('export');
        foreach ($this->getFiles() as $file) {
            $disk->delete($file);
        }
    }
}
