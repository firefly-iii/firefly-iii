<?php
/**
 * CsvProcessor.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Import\FileProcessor;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Iterator;
use League\Csv\Reader;
use Log;

/**
 * Class CsvProcessor, as the name suggests, goes over CSV file line by line and creates
 * "ImportJournal" objects, which are used in another step to create new journals and transactions
 * and what-not.
 */
class CsvProcessor implements FileProcessorInterface
{
    /** @var ImportJob */
    private $job;
    /** @var Collection */
    private $objects;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var array */
    private $validConverters = [];
    /** @var array */
    private $validSpecifics = [];

    /**
     * FileProcessorInterface constructor.
     */
    public function __construct()
    {
        $this->objects         = new Collection;
        $this->validSpecifics  = array_keys(config('csv.import_specifics'));
        $this->validConverters = array_keys(config('csv.import_roles'));
    }

    /**
     * @return Collection
     * @throws FireflyException
     */
    public function getObjects(): Collection
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call getObjects() without a job.');
        }

        return $this->objects;
    }

    /**
     * Does the actual job.
     *
     * @return bool
     *
     * @throws \League\Csv\Exception
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws FireflyException
     */
    public function run(): bool
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call run() without a job.');
        }
        Log::debug('Now in CsvProcessor run(). Job is now running...');

        $entries = new Collection($this->getImportArray());
        $this->addStep();
        Log::notice('Building importable objects from CSV file.');
        Log::debug(sprintf('Number of entries: %d', $entries->count()));
        $notImported = $entries->filter(
            function (array $row, int $index) {
                $row = array_values($row);
                if ($this->rowAlreadyImported($row)) {
                    $message = sprintf('Row #%d has already been imported.', $index);
                    $this->repository->addError($this->job, $index, $message);
                    Log::info($message);

                    return null;
                }

                return $row;
            }
        );
        $this->addStep();
        Log::debug(sprintf('Number of entries left: %d', $notImported->count()));

        $notImported->each(
            function (array $row, int $index) {
                $journal = $this->importRow($index, $row);
                $this->objects->push($journal);
            }
        );
        $this->addStep();

        return true;
    }

    /**
     * Shorthand method to set the extended status.
     *
     * @codeCoverageIgnore
     *
     * @param array $array
     */
    public function setExtendedStatus(array $array)
    {
        $this->repository->setExtendedStatus($this->job, $array);
    }

    /**
     * Set import job for this processor.
     *
     * @param ImportJob $job
     *
     * @return FileProcessorInterface
     */
    public function setJob(ImportJob $job): FileProcessorInterface
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

        return $this;
    }

    /**
     * Shorthand method to add a step.
     *
     * @codeCoverageIgnore
     */
    private function addStep()
    {
        $this->repository->addStepsDone($this->job, 1);
    }

    /**
     * Add meta data to the individual value and verify that it can be handled in a later stage.
     *
     * @param int    $index
     * @param string $value
     *
     * @return array
     *
     * @throws FireflyException
     */
    private function annotateValue(int $index, string $value)
    {
        $config = $this->getConfig();
        $role   = $config['column-roles'][$index] ?? '_ignore';
        $mapped = $config['column-mapping-config'][$index][$value] ?? null;

        // throw error when not a valid converter.
        if (!in_array($role, $this->validConverters)) {
            throw new FireflyException(sprintf('"%s" is not a valid role.', $role));
        }

        $entry = [
            'role'   => $role,
            'value'  => $value,
            'mapped' => $mapped,
        ];

        return $entry;
    }

    /**
     * Shorthand method to return configuration.
     *
     * @codeCoverageIgnore
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * @return Iterator
     *
     * @throws \League\Csv\Exception
     * @throws \League\Csv\Exception
     */
    private function getImportArray(): Iterator
    {
        $content    = $this->repository->uploadFileContents($this->job);
        $config     = $this->getConfig();
        $reader     = Reader::createFromString($content);
        $delimiter  = $config['delimiter'] ?? ',';
        $hasHeaders = isset($config['has-headers']) ? $config['has-headers'] : false;
        if ('tab' === $delimiter) {
            $delimiter = "\t"; // @codeCoverageIgnore
        }
        $reader->setDelimiter($delimiter);
        if ($hasHeaders) {
            $reader->setHeaderOffset(0); // @codeCoverageIgnore
        }
        $results = $reader->getRecords();
        Log::debug('Created a CSV reader.');

        return $results;
    }

    /**
     * Will return string representation of JSON error code.
     *
     * @param int $jsonError
     *
     * @codeCoverageIgnore
     * @return string
     */
    private function getJsonError(int $jsonError): string
    {
        $messages = [
            JSON_ERROR_NONE                  => 'No JSON error',
            JSON_ERROR_DEPTH                 => 'The maximum stack depth has been exceeded.',
            JSON_ERROR_STATE_MISMATCH        => 'Invalid or malformed JSON.',
            JSON_ERROR_CTRL_CHAR             => 'Control character error, possibly incorrectly encoded.',
            JSON_ERROR_SYNTAX                => 'Syntax error.',
            JSON_ERROR_UTF8                  => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
            JSON_ERROR_RECURSION             => 'One or more recursive references in the value to be encoded.',
            JSON_ERROR_INF_OR_NAN            => 'One or more NAN or INF values in the value to be encoded.',
            JSON_ERROR_UNSUPPORTED_TYPE      => 'A value of a type that cannot be encoded was given.',
            JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given.',
            JSON_ERROR_UTF16                 => 'Malformed UTF-16 characters, possibly incorrectly encoded.',
        ];
        if (isset($messages[$jsonError])) {
            return $messages[$jsonError];
        }

        return 'Unknown JSON error';
    }

    /**
     * Hash an array and return the result.
     *
     * @param array $array
     *
     * @return string
     *
     * @throws FireflyException
     */
    private function getRowHash(array $array): string
    {
        $json      = json_encode($array);
        $jsonError = json_last_error();

        if (false === $json) {
            throw new FireflyException(sprintf('Error while encoding JSON for CSV row: %s', $this->getJsonError($jsonError)));  // @codeCoverageIgnore
        }
        $hash = hash('sha256', $json);

        return $hash;
    }

    /**
     * Take a row, build import journal by annotating each value and storing it in the import journal.
     *
     * @param int   $index
     * @param array $row
     *
     * @return ImportJournal
     *
     * @throws FireflyException
     */
    private function importRow(int $index, array $row): ImportJournal
    {
        $row = array_values($row);
        Log::debug(sprintf('Now at row %d', $index));
        $row    = $this->specifics($row);
        $hash   = $this->getRowHash($row);
        $config = $this->getConfig();

        $journal = new ImportJournal;
        $journal->setUser($this->job->user);
        $journal->setHash($hash);

        /**
         * @var int
         * @var string $value
         */
        foreach ($row as $rowIndex => $value) {
            $value = trim(strval($value));
            if (strlen($value) > 0) {
                $annotated = $this->annotateValue($rowIndex, $value);
                Log::debug('Annotated value', $annotated);
                $journal->setValue($annotated);
            }
        }
        // set some extra info:
        $importAccount = intval($config['import-account'] ?? 0);
        $journal->asset->setDefaultAccountId($importAccount);

        return $journal;
    }

    /**
     * Checks if the row has not been imported before.
     *
     * @param array $array
     *
     * @return bool
     *
     * @throws FireflyException
     */
    private function rowAlreadyImported(array $array): bool
    {
        $hash  = $this->getRowHash($array);
        $count = $this->repository->countByHash($hash);
        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * And this is the point where the specifix go to work.
     *
     * @param array $row
     *
     * @return array
     *
     * @throws FireflyException
     */
    private function specifics(array $row): array
    {
        $config = $this->getConfig();
        $names  = array_keys($config['specifics'] ?? []);
        foreach ($names as $name) {
            if (!in_array($name, $this->validSpecifics)) {
                throw new FireflyException(sprintf('"%s" is not a valid class name', $name));
            }

            /** @var SpecificInterface $specific */
            $specific = app('FireflyIII\Import\Specifics\\' . $name);

            // it returns the row, possibly modified:
            $row = $specific->run($row);
        }

        return $row;
    }
}
