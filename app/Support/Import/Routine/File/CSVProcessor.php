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
use FireflyIII\Support\Import\Placeholder\ColumnValue;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
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
    /** @var array */
    private $config;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $mappedValues;
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


        // in order to actually map we also need to read the FULL file.
        try {
            $reader = $this->getReader();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        // get all lines from file:
        $lines = $this->getLines($reader);

        // make import objects, according to their role:
        $importables = $this->processLines($lines);

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
        $this->config      = $job->configuration;
        $this->repository  = app(ImportJobRepositoryInterface::class);
        $this->attachments = app(AttachmentHelperInterface::class);
        $this->repository->setUser($job->user);

    }

    /**
     * Returns all lines from the CSV file.
     *
     * @param Reader $reader
     *
     * @return array
     * @throws FireflyException
     */
    private function getLines(Reader $reader): array
    {
        $offset = isset($this->config['has-headers']) && $this->config['has-headers'] === true ? 1 : 0;
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
     * If the value in the column is mapped to a certain ID,
     * the column where this ID must be placed will change.
     *
     * For example, if you map role "budget-name" with value "groceries" to 1,
     * then that should become the budget-id. Not the name.
     *
     * @param int $column
     * @param int $mapped
     *
     * @return string
     * @throws FireflyException
     */
    private function getRoleForColumn(int $column, int $mapped): string
    {
        $roles = $this->config['column-roles'];
        $role  = $roles[$column] ?? '_ignore';
        if ($mapped === 0) {
            Log::debug(sprintf('Column #%d with role "%s" is not mapped.', $column, $role));

            return $role;
        }
        if (!(isset($this->config['column-do-mapping'][$column]) && $this->config['column-do-mapping'][$column] === true)) {

            return $role;
        }
        switch ($role) {
            default:
                throw new FireflyException(sprintf('Cannot indicate new role for mapped role "%s"', $role));
            case 'account-id':
            case 'account-name':
            case 'account-iban':
            case 'account-number':
                $newRole = 'account-id';
                break;
            case 'foreign-currency-id':
            case 'foreign-currency-code':
                $newRole = 'foreign-currency-id';
                break;
            case 'bill-id':
            case 'bill-name':
                $newRole = 'bill-id';
                break;
            case 'currency-id':
            case 'currency-name':
            case 'currency-code':
            case 'currency-symbol':
                $newRole = 'currency-id';
                break;
            case 'budget-id':
            case 'budget-name':
                $newRole = 'budget-id';
                break;
            case 'category-id':
            case 'category-name':
                $newRole = 'category-id';
                break;
            case 'opposing-id':
            case 'opposing-name':
            case 'opposing-iban':
            case 'opposing-number':
                $newRole = 'opposing-id';
                break;
        }
        Log::debug(sprintf('Role was "%s", but because of mapping, role becomes "%s"', $role, $newRole));

        // also store the $mapped values in a "mappedValues" array.
        $this->mappedValues[$newRole] = $mapped;

        return $newRole;
    }


    /**
     * Process all lines in the CSV file. Each line is processed separately.
     *
     * @param array $lines
     *
     * @return array
     * @throws FireflyException
     */
    private function processLines(array $lines): array
    {
        $processed = [];
        $count     = \count($lines);
        foreach ($lines as $index => $line) {
            Log::debug(sprintf('Now at line #%d of #%d', $index, $count));
            $processed[] = $this->processSingleLine($line);
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
     * @return ImportTransaction
     * @throws FireflyException
     */
    private function processSingleLine(array $line): ImportTransaction
    {
        $transaction = new ImportTransaction;
        // todo run all specifics on row.
        foreach ($line as $column => $value) {
            $value        = trim($value);
            $originalRole = $this->config['column-roles'][$column] ?? '_ignore';
            if (\strlen($value) > 0 && $originalRole !== '_ignore') {

                // is a mapped value present?
                $mapped = $this->config['column-mapping-config'][$column][$value] ?? 0;
                // the role might change.
                $role = $this->getRoleForColumn($column, $mapped);

                $columnValue = new ColumnValue;
                $columnValue->setValue($value);
                $columnValue->setRole($role);
                $columnValue->setMappedValue($mapped);
                $columnValue->setOriginalRole($originalRole);
                $transaction->addColumnValue($columnValue);

                // create object that parses this column value.

                Log::debug(sprintf('Now at column #%d (%s), value "%s"', $column, $role, $value));
            }
        }

        return $transaction;
    }
}