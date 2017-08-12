<?php
/**
 * CsvProcessor.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
     * Does the actual job:
     *
     * @return bool
     */
    public function run(): bool
    {
        Log::debug('Now in CsvProcessor run(). Job is now running...');

        $entries = $this->getImportArray();
        $index   = 0;
        Log::notice('Building importable objects from CSV file.');
        foreach ($entries as $index => $row) {
            // verify if not exists already:
            if ($this->rowAlreadyImported($row)) {
                $message = sprintf('Row #%d has already been imported.', $index);
                $this->job->addError($index, $message);
                $this->job->addStepsDone(5); // all steps.
                Log::info($message);
                continue;
            }
            $this->objects->push($this->importRow($index, $row));
            $this->job->addStepsDone(1);
        }
        // if job has no step count, set it now:
        $extended = $this->job->extended_status;
        if ($extended['steps'] === 0) {
            $extended['steps']          = $index * 5;
            $this->job->extended_status = $extended;
            $this->job->save();
        }


        return true;
    }

    /**
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
        $value  = trim($value);
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
        $content = $this->job->uploadFileContents();
        $config  = $this->job->configuration;
        $reader  = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);
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
        switch ($jsonError) {
            default:
                return 'Unknown JSON error';
            case JSON_ERROR_NONE:
                return 'No JSON error';
            case JSON_ERROR_DEPTH:
                return 'JSON_ERROR_DEPTH';
            case JSON_ERROR_STATE_MISMATCH:
                return 'JSON_ERROR_STATE_MISMATCH';
            case JSON_ERROR_CTRL_CHAR:
                return 'JSON_ERROR_CTRL_CHAR';
            case JSON_ERROR_SYNTAX:
                return 'JSON_ERROR_SYNTAX';
            case JSON_ERROR_UTF8:
                return 'JSON_ERROR_UTF8';
            case JSON_ERROR_RECURSION:
                return 'JSON_ERROR_RECURSION';
            case JSON_ERROR_INF_OR_NAN:
                return 'JSON_ERROR_INF_OR_NAN';
            case JSON_ERROR_UNSUPPORTED_TYPE:
                return 'JSON_ERROR_UNSUPPORTED_TYPE';
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                return 'JSON_ERROR_INVALID_PROPERTY_NAME';
            case JSON_ERROR_UTF16:
                return 'JSON_ERROR_UTF16';
        }
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
        $row       = $this->specifics($row);
        $json      = json_encode($row);
        $jsonError = json_last_error();

        if ($json === false) {
            throw new FireflyException(sprintf('Error while encoding JSON: %s', $this->getJsonError($jsonError)));
        }

        $journal = new ImportJournal;
        $journal->setUser($this->job->user);
        $journal->setHash(hash('sha256', $json));

        foreach ($row as $rowIndex => $value) {
            $value = trim($value);
            if (strlen($value) > 0) {
                $annotated = $this->annotateValue($rowIndex, $value);
                Log::debug('Annotated value', $annotated);
                $journal->setValue($annotated);
            }
        }
        Log::debug('ImportJournal complete, returning.');

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
        $string = json_encode($array);
        $hash   = hash('sha256', json_encode($string));
        $json   = json_encode($hash);
        $entry  = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
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
        //
        foreach ($config['specifics'] as $name => $enabled) {

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
