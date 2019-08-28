<?php
/**
 * LineReader.php
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

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\File;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;

/**
 * Class LineReader
 */
class LineReader
{
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Grab all lines from the import job.
     *
     * @return array
     * @throws FireflyException
     */
    public function getLines(): array
    {
        try {
            $reader = $this->getReader();
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
        // get all lines from file:
        $lines = $this->getAllLines($reader);

        // apply specifics and return.
        return $this->applySpecifics($lines);
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob   = $importJob;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);
        $this->repository->setUser($importJob->user);
    }

    /**
     * @param array $lines
     *
     * @return array
     */
    private function applySpecifics(array $lines): array
    {
        $config         = $this->importJob->configuration;
        $validSpecifics = array_keys(config('csv.import_specifics'));
        $specifics      = $config['specifics'] ?? [];
        $names          = array_keys($specifics);
        $toApply        = [];
        foreach ($names as $name) {
            if (!in_array($name, $validSpecifics, true)) {
                continue;
            }
            $class     = config(sprintf('csv.import_specifics.%s', $name));
            $toApply[] = app($class);
        }
        $return = [];
        /** @var array $line */
        foreach ($lines as $line) {
            /** @var SpecificInterface $specific */
            foreach ($toApply as $specific) {
                $line = $specific->run($line);
            }
            $return[] = $line;
        }

        return $return;
    }

    /**
     * @param Reader $reader
     *
     * @return array
     * @throws FireflyException
     */
    private function getAllLines(Reader $reader): array
    {
        /** @var array $config */
        $config = $this->importJob->configuration;
        Log::debug('now in getLines()');
        $offset = isset($config['has-headers']) && true === $config['has-headers'] ? 1 : 0;
        try {
            $stmt = (new Statement)->offset($offset);
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Could not execute statement: %s', $e->getMessage()));
        }
        // @codeCoverageIgnoreEnd
        $results = $stmt->process($reader);
        $lines   = [];
        foreach ($results as $line) {

            $lineValues = array_values($line);

            // do a first sanity check on whatever comes out of the CSV file.
            array_walk(
                $lineValues, static function ($element) {
                $element = str_replace('&nbsp;', ' ', (string)$element);

                return $element;
            }
            );

            $lines[] = $lineValues;
        }


        return $lines;
    }

    /**
     * @return Reader
     * @throws FireflyException
     */
    private function getReader(): Reader
    {
        Log::debug('Now in getReader()');
        $content    = '';
        $collection = $this->repository->getAttachments($this->importJob);
        /** @var Attachment $attachment */
        foreach ($collection as $attachment) {
            if ('import_file' === $attachment->filename) {
                $content = $this->attachments->getAttachmentContent($attachment);
                break;
            }
        }
        $config = $this->repository->getConfiguration($this->importJob);
        $reader = Reader::createFromString($content);
        try {
            $reader->setDelimiter($config['delimiter'] ?? ',');
            // @codeCoverageIgnoreStart
        } catch (\League\Csv\Exception $e) {
            throw new FireflyException(sprintf('Cannot set delimiter: %s', $e->getMessage()));
        }

        // @codeCoverageIgnoreEnd

        return $reader;
    }


}
