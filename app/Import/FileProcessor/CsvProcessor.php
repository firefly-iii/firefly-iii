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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\FileProcessor;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Support\Collection;
use Iterator;
use League\Csv\Reader;
use Log;

/**
 * Class CsvProcessor, as the name suggests, goes over CSV file line by line and creates
 * "ImportJournal" objects, which are used in another step to create new journals and transactions
 * and what-not.
 *
 * @package FireflyIII\Import\FileProcessor
 */
class CsvProcessor implements FileProcessorInterface
{
    /** @var  ImportJob */
    private $job;
    /** @var Collection */
    private $objects;
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
     */
    public function getObjects(): Collection
    {
        return $this->objects;
    }

    /**
     * Does the actual job.
     *
     * @return bool
     */
    public function run(): bool
    {
        Log::debug('Now in CsvProcessor run(). Job is now running...');

        $entries = new Collection($this->getImportArray());
        Log::notice('Building importable objects from CSV file.');
        Log::debug(sprintf('Number of entries: %d', $entries->count()));
        $notImported = $entries->filter(
            function (array $row, int $index) {
                if ($this->rowAlreadyImported($row)) {
                    $message = sprintf('Row #%d has already been imported.', $index);
                    $this->job->addError($index, $message);
                    $this->job->addStepsDone(5); // all steps.
                    Log::info($message);

                    return null;
                }

                return $row;
            }
        );
        Log::debug(sprintf('Number of entries left: %d', $notImported->count()));

        // set (new) number of steps:
        $status                     = $this->job->extended_status;
        $status['steps']            = $notImported->count() * 5;
        $this->job->extended_status = $status;
        $this->job->save();
        Log::debug(sprintf('Number of steps: %d', $notImported->count() * 5));

        $notImported->each(
            function (array $row, int $index) {
                $journal = $this->importRow($index, $row);
                $this->objects->push($journal);
                $this->job->addStepsDone(1);
            }
        );

        return true;
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
        $this->job = $job;

        return $this;
    }

    /**
     * Add meta data to the individual value and verify that it can be handled in a later stage.
     *
     * @param int    $index
     * @param string $value
     *
     * @return array
     * @throws FireflyException
     */
    private function annotateValue(int $index, string $value)
    {
        $config = $this->job->configuration;
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
     * @return Iterator
     */
    private function getImportArray(): Iterator
    {
        $content   = $this->job->uploadFileContents();
        $config    = $this->job->configuration;
        $reader    = Reader::createFromString($content);
        $delimiter = $config['delimiter'];
        if ($delimiter === 'tab') {
            $delimiter = "\t";
        }
        $reader->setDelimiter($delimiter);
        $start   = $config['has-headers'] ? 1 : 0;
        $results = $reader->setOffset($start)->fetch();
        Log::debug(sprintf('Created a CSV reader starting at offset %d', $start));

        return $results;
    }

    /**
     * Will return string representation of JSON error code.
     *
     * @param int $jsonError
     *
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
     * @throws FireflyException
     */
    private function getRowHash(array $array): string
    {
        $json      = json_encode($array);
        $jsonError = json_last_error();

        if ($json === false) {
            throw new FireflyException(sprintf('Error while encoding JSON for CSV row: %s', $this->getJsonError($jsonError)));
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
     * @throws FireflyException
     */
    private function importRow(int $index, array $row): ImportJournal
    {
        Log::debug(sprintf('Now at row %d', $index));
        $row  = $this->specifics($row);
        $hash = $this->getRowHash($row);

        $journal = new ImportJournal;
        $journal->setUser($this->job->user);
        $journal->setHash($hash);

        /**
         * @var int    $rowIndex
         * @var string $value
         */
        foreach ($row as $rowIndex => $value) {
            $value = trim($value);
            if (strlen($value) > 0) {
                $annotated = $this->annotateValue($rowIndex, $value);
                Log::debug('Annotated value', $annotated);
                $journal->setValue($annotated);
            }
        }
        // set some extra info:
        $journal->asset->setDefaultAccountId($this->job->configuration['import-account']);

        return $journal;
    }

    /**
     * Checks if the row has not been imported before.
     *
     * @param array $array
     *
     * @return bool
     */
    private function rowAlreadyImported(array $array): bool
    {
        $hash  = $this->getRowHash($array);
        $json  = json_encode($hash);
        $entry = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                       ->where('data', $json)
                                       ->where('name', 'importHash')
                                       ->first();
        if (!is_null($entry)) {
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
     * @throws FireflyException
     */
    private function specifics(array $row): array
    {
        $config = $this->job->configuration;
        $names  = array_keys($config['specifics']);
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
