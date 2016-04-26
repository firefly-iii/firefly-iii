<?php
declare(strict_types = 1);
/**
 * Processor.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use Auth;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\Entry\Entry;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCollector;
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
     * @return bool
     */
    public function collectAttachments(): bool
    {
        $attachmentCollector = app('FireflyIII\Export\Collector\AttachmentCollector', [$this->job]);
        $attachmentCollector->run();
        $this->files = $this->files->merge($attachmentCollector->getFiles());

        return true;
    }

    /**
     * @return bool
     */
    public function collectJournals(): bool
    {
        $args = [$this->accounts, Auth::user(), $this->settings['startDate'], $this->settings['endDate']];
        /** @var JournalCollector $journalCollector */
        $journalCollector = app('FireflyIII\Repositories\Journal\JournalCollector', $args);
        $this->journals   = $journalCollector->collect();
        Log::debug(
            'Collected ' .
            $this->journals->count() . ' journals (between ' .
            $this->settings['startDate']->format('Y-m-d') . ' and ' .
            $this->settings['endDate']->format('Y-m-d')
            . ').'
        );

        return true;
    }

    /**
     * @return bool
     */
    public function collectOldUploads(): bool
    {
        $uploadCollector = app('FireflyIII\Export\Collector\UploadCollector', [$this->job]);
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
        Log::debug('Converted ' . $count . ' journals to "Entry" objects.');

        return true;
    }

    /**
     * @return bool
     */
    public function createConfigFile(): bool
    {
        $this->configurationMaker = app('FireflyIII\Export\ConfigurationFile', [$this->job]);
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

        return true;
    }

    /**
     * @return bool
     */
    public function exportJournals(): bool
    {
        $exporterClass = Config::get('firefly.export_formats.' . $this->exportFormat);
        $exporter      = app($exporterClass, [$this->job]);
        Log::debug('Going to export ' . $this->exportEntries->count() . ' export entries into ' . $this->exportFormat . ' format.');
        $exporter->setEntries($this->exportEntries);
        $exporter->run();
        $this->files->push($exporter->getFileName());
        Log::debug('Added "' . $exporter->getFileName() . '" to the list of files to include in the zip.');

        return true;
    }

    /**
     * @return Collection
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }
}
