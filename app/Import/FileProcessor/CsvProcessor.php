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
use FireflyIII\Import\Converter\ConverterInterface;
use FireflyIII\Import\Object\ImportObject;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;
use Iterator;
use League\Csv\Reader;
use Log;

/**
 * Class CsvProcessor
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
     * @return bool
     */
    public function run(): bool
    {
        // update the job and say we started:
        $this->job->status = 'running';
        $this->job->save();

        Log::debug('Mapping config: ', $this->job->configuration['column-mapping-config']);

        $entries = $this->getImportArray();
        $count   = 0;
        Log::notice('Building importable objects from CSV file.');
        foreach ($entries as $index => $row) {
            $importObject = $this->importRow($index, $row);
            $this->objects->push($importObject);
            /**
             * 1. Build import entry.
             * 2. Validate import entry.
             * 3. Store journal.
             * 4. Run rules.
             */
            $this->job->addTotalSteps(4);
            $this->job->addStepsDone(1);
            $count++;
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

        return $results;
    }

    /**
     * @param int   $index
     * @param array $row
     *
     * @return ImportObject
     */
    private function importRow(int $index, array $row): ImportObject
    {
        Log::debug(sprintf('Now at row %d', $index));
        $row    = $this->specifics($row);
        $object = new ImportObject();
        $object->setUser($this->job->user);
        $object->setHash(hash('sha256', json_encode($row)));

        foreach ($row as $rowIndex => $value) {
            $annotated = $this->annotateValue($rowIndex, $value);
            Log::debug('Annotated value: ', $annotated);
            $object->setValue($annotated);
        }

        return $object;
    }

    /**
     * @param int    $index
     * @param string $value
     *
     * @return array
     * @throws FireflyException
     */
    private function importValue(int $index, string $value): array
    {
        $config = $this->job->configuration;
        $role   = $config['column-roles'][$index] ?? '_ignore';
        $doMap  = $config['column-do-mapping'][$index] ?? false;

        // throw error when not a valid converter.
        if (!in_array($role, $this->validConverters)) {
            throw new FireflyException(sprintf('"%s" is not a valid role.', $role));
        }

        $converterClass = config(sprintf('csv.import_roles.%s.converter', $role));
        $mapping        = $config['column-mapping-config'][$index] ?? [];
        $className      = sprintf('FireflyIII\\Import\\Converter\\%s', $converterClass);

        /** @var ConverterInterface $converter */
        $converter = app($className);
        // set some useful values for the converter:
        $converter->setMapping($mapping);
        $converter->setDoMap($doMap);
        $converter->setUser($this->job->user);
        $converter->setConfig($config);

        // run the converter for this value:
        $convertedValue = $converter->convert($value);
        $certainty      = $converter->getCertainty();

        // log it.
        Log::debug('Value ', ['index' => $index, 'value' => $value, 'role' => $role]);

        // store in import entry:
        Log::debug('Going to import', ['role' => $role, 'value' => $value, 'certainty' => $certainty]);

        return [
            'role'      => $role,
            'certainty' => $certainty,
            'value'     => $convertedValue,
        ];

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