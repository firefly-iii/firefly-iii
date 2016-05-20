<?php
/**
 * Processor.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Export;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\Collector\AttachmentCollector;
use FireflyIII\Export\Collector\UploadCollector;
use FireflyIII\Export\Entry\Entry;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
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
     * @return bool
     */
    public function collectAttachments(): bool
    {
        /** @var AttachmentCollector $attachmentCollector */
        $attachmentCollector = app(AttachmentCollector::class, [$this->job]);
        $attachmentCollector->run();
        $this->files = $this->files->merge($attachmentCollector->getFiles());

        return true;
    }

    /**
     * @return bool
     */
    public function collectJournals(): bool
    {
        /** @var JournalRepositoryInterface $repository */
        $repository     = app(JournalRepositoryInterface::class);
        $this->journals = $repository->getJournalsInRange($this->accounts, $this->settings['startDate'], $this->settings['endDate']);

        return true;
    }

    /**
     * @return bool
     */
    public function collectOldUploads(): bool
    {
        /** @var UploadCollector $uploadCollector */
        $uploadCollector = app(UploadCollector::class, [$this->job]);
        $uploadCollector->run();

        $this->files = $this->files->merge($uploadCollector->getFiles());

        return true;
    }

    /**
     * @return bool
     */
    public function convertJournals(): bool
    {
        $count = 0;
        /** @var TransactionJournal $journal */
        foreach ($this->journals as $journal) {
            $this->exportEntries->push(Entry::fromJournal($journal));
            $count++;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function createConfigFile(): bool
    {
        $this->configurationMaker = app(ConfigurationFile::class, [$this->job]);
        $this->files->push($this->configurationMaker->make());

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
        $this->deleteFiles($disk);

        return true;
    }

    /**
     * @return bool
     */
    public function exportJournals(): bool
    {
        $exporterClass = config('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass, [$this->job]);
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
     * @param FilesystemAdapter $disk
     */
    private function deleteFiles(FilesystemAdapter $disk)
    {
        foreach ($this->getFiles() as $file) {
            $disk->delete($file);
        }
    }
}
