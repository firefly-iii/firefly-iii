<?php
/**
 * CSVProcessor.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;


/**
 * Class CSVProcessor
 *
 * @package FireflyIII\Support\Import\Routine\File
 */
class CSVProcessor implements FileProcessorInterface
{
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Fires the file processor.
     *
     * @return array
     * @throws FireflyException
     */
    public function run(): array
    {
        $config = $this->importJob->configuration;

        // in order to actually map we also need to read the FULL file.
        try {
            $reader = $this->getReader();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        // get mapping from config
        $roles = $config['column-roles'];

        // get all lines from file:
        $lines = $this->getLines($reader, $config);

        // make import objects, according to their role:
        $importables = $this->processLines($lines, $roles);

        echo '<pre>';
        print_r($importables);
        print_r($lines);

        exit;
        die('here we are');
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob   = $job;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);
        $this->repository->setUser($job->user);

    }

    /**
     * Returns all lines from the CSV file.
     *
     * @param Reader $reader
     * @param array  $config
     *
     * @return array
     * @throws FireflyException
     */
    private function getLines(Reader $reader, array $config): array
    {
        $offset = isset($config['has-headers']) && $config['has-headers'] === true ? 1 : 0;
        try {
            $stmt = (new Statement)->offset($offset);
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Could not execute statement: %s', $e->getMessage()));
        }
        $results = $stmt->process($reader);
        $lines   = [];
        foreach ($results as $line) {
            $lines[] = array_values($line);
        }

        return $lines;
    }

    /**
     * Return an instance of a CSV file reader so content of the file can be read.
     *
     * @throws \League\Csv\Exception
     */
    private function getReader(): Reader
    {
        $content = '';
        /** @var Collection $collection */
        $collection = $this->importJob->attachments;
        /** @var Attachment $attachment */
        foreach ($collection as $attachment) {
            if ($attachment->filename === 'import_file') {
                $content = $this->attachments->getAttachmentContent($attachment);
                break;
            }
        }
        $config = $this->repository->getConfiguration($this->importJob);
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);

        return $reader;
    }

    /**
     * Process a single column. Return is an array with:
     * [0 => key, 1 => value]
     * where the first item is the key under which the value
     * must be stored, and the second is the value.
     *
     * @param int    $column
     * @param string $value
     * @param string $role
     *
     * @return array
     * @throws FireflyException
     */
    private function processColumn(int $column, string $value, string $role): array
    {
        switch ($role) {
            default:
                throw new FireflyException(sprintf('Cannot handle role "%s" with value "%s"', $role, $value));


                // feed each line into a new class which will process
            // the line. 
        }
    }

    /**
     * Process all lines in the CSV file. Each line is processed separately.
     *
     * @param array $lines
     * @param array $roles
     *
     * @return array
     * @throws FireflyException
     */
    private function processLines(array $lines, array $roles): array
    {
        $processed = [];
        foreach ($lines as $line) {
            $processed[] = $this->processSingleLine($line, $roles);

        }

        return $processed;
    }

    /**
     * Process a single line in the CSV file.
     * Each column is processed separately.
     *
     * @param array $line
     * @param array $roles
     *
     * @return array
     * @throws FireflyException
     */
    private function processSingleLine(array $line, array $roles): array
    {
        // todo run all specifics on row.
        $transaction = [];
        foreach ($line as $column => $value) {
            $value = trim($value);
            $role  = $roles[$column] ?? '_ignore';
            [$key, $result] = $this->processColumn($column, $value, $role);
            // if relevant, find mapped value:
        }

        return $transaction;
    }
}