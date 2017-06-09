<?php
/**
 * CsvImportSupportTrait.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Import\MapperPreProcess\PreProcessorInterface;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use League\Csv\Reader;
use Log;

/**
 * Trait CsvImportSupportTrait
 *
 * @property ImportJob $job
 *
 * @package FireflyIII\Support\Import
 */
trait CsvImportSupportTrait
{
    /**
     * @return bool
     */
    protected function doColumnMapping(): bool
    {
        $mapArray = $this->job->configuration['column-do-mapping'] ?? [];
        $doMap    = false;
        foreach ($mapArray as $value) {
            if ($value === true) {
                $doMap = true;
                break;
            }
        }

        return $this->job->configuration['column-mapping-complete'] === false && $doMap;
    }

    /**
     * @return bool
     */
    protected function doColumnRoles(): bool
    {
        return $this->job->configuration['column-roles-complete'] === false;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    protected function getDataForColumnMapping(): array
    {
        $config  = $this->job->configuration;
        $data    = [];
        $indexes = [];

        foreach ($config['column-do-mapping'] as $index => $mustBeMapped) {
            if ($mustBeMapped) {

                $column = $config['column-roles'][$index] ?? '_ignore';

                // is valid column?
                $validColumns = array_keys(config('csv.import_roles'));
                if (!in_array($column, $validColumns)) {
                    throw new FireflyException(sprintf('"%s" is not a valid column.', $column));
                }

                $canBeMapped   = config('csv.import_roles.' . $column . '.mappable');
                $preProcessMap = config('csv.import_roles.' . $column . '.pre-process-map');
                if ($canBeMapped) {
                    $mapperClass = config('csv.import_roles.' . $column . '.mapper');
                    $mapperName  = sprintf('\\FireflyIII\\Import\Mapper\\%s', $mapperClass);
                    /** @var MapperInterface $mapper */
                    $mapper       = new $mapperName;
                    $indexes[]    = $index;
                    $data[$index] = [
                        'name'          => $column,
                        'mapper'        => $mapperName,
                        'index'         => $index,
                        'options'       => $mapper->getMap(),
                        'preProcessMap' => null,
                        'values'        => [],
                    ];
                    if ($preProcessMap) {
                        $preClass                      = sprintf(
                            '\\FireflyIII\\Import\\MapperPreProcess\\%s',
                            config('csv.import_roles.' . $column . '.pre-process-mapper')
                        );
                        $data[$index]['preProcessMap'] = $preClass;
                    }
                }

            }
        }

        // in order to actually map we also need all possible values from the CSV file.
        $content = $this->job->uploadFileContents();
        /** @var Reader $reader */
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);
        $results        = $reader->fetch();
        $validSpecifics = array_keys(config('csv.import_specifics'));

        foreach ($results as $rowIndex => $row) {

            // skip first row?
            if ($rowIndex === 0 && $config['has-headers']) {
                continue;
            }

            // run specifics here:
            // and this is the point where the specifix go to work.
            foreach ($config['specifics'] as $name => $enabled) {

                if (!in_array($name, $validSpecifics)) {
                    throw new FireflyException(sprintf('"%s" is not a valid class name', $name));
                }
                $class = config('csv.import_specifics.' . $name);
                /** @var SpecificInterface $specific */
                $specific = app($class);

                // it returns the row, possibly modified:
                $row = $specific->run($row);
            }

            //do something here
            foreach ($indexes as $index) { // this is simply 1, 2, 3, etc.
                if (!isset($row[$index])) {
                    // don't really know how to handle this. Just skip, for now.
                    continue;
                }
                $value = $row[$index];
                if (strlen($value) > 0) {

                    // we can do some preprocessing here,
                    // which is exclusively to fix the tags:
                    if (!is_null($data[$index]['preProcessMap'])) {
                        /** @var PreProcessorInterface $preProcessor */
                        $preProcessor           = app($data[$index]['preProcessMap']);
                        $result                 = $preProcessor->run($value);
                        $data[$index]['values'] = array_merge($data[$index]['values'], $result);

                        Log::debug($rowIndex . ':' . $index . 'Value before preprocessor', ['value' => $value]);
                        Log::debug($rowIndex . ':' . $index . 'Value after preprocessor', ['value-new' => $result]);
                        Log::debug($rowIndex . ':' . $index . 'Value after joining', ['value-complete' => $data[$index]['values']]);


                        continue;
                    }

                    $data[$index]['values'][] = $value;
                }
            }
        }
        foreach ($data as $index => $entry) {
            $data[$index]['values'] = array_unique($data[$index]['values']);
        }

        return $data;
    }

    /**
     * This method collects the data that will enable a user to choose column content.
     *
     * @return array
     */
    protected function getDataForColumnRoles(): array
    {
        Log::debug('Now in getDataForColumnRoles()');
        $config = $this->job->configuration;
        $data   = [
            'columns'       => [],
            'columnCount'   => 0,
            'columnHeaders' => [],
        ];

        // show user column role configuration.
        $content = $this->job->uploadFileContents();

        // create CSV reader.
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);
        $start  = $config['has-headers'] ? 1 : 0;
        $end    = $start + config('csv.example_rows');
        $header = [];
        if ($config['has-headers']) {
            $header = $reader->fetchOne(0);
        }


        // collect example data in $data['columns']
        Log::debug(sprintf('While %s is smaller than %d', $start, $end));
        while ($start < $end) {
            $row = $reader->fetchOne($start);
            Log::debug(sprintf('Row %d has %d columns', $start, count($row)));
            // run specifics here:
            // and this is the point where the specifix go to work.
            foreach ($config['specifics'] as $name => $enabled) {
                /** @var SpecificInterface $specific */
                $specific = app('FireflyIII\Import\Specifics\\' . $name);
                Log::debug(sprintf('Will now apply specific "%s" to row %d.', $name, $start));
                // it returns the row, possibly modified:
                $row = $specific->run($row);
            }

            foreach ($row as $index => $value) {
                $value                         = trim($value);
                $data['columnHeaders'][$index] = $header[$index] ?? '';
                if (strlen($value) > 0) {
                    $data['columns'][$index][] = $value;
                }
            }
            $start++;
            $data['columnCount'] = count($row) > $data['columnCount'] ? count($row) : $data['columnCount'];
        }

        // make unique example data
        foreach ($data['columns'] as $index => $values) {
            $data['columns'][$index] = array_unique($values);
        }

        $data['set_roles'] = [];
        // collect possible column roles:
        $data['available_roles'] = [];
        foreach (array_keys(config('csv.import_roles')) as $role) {
            $data['available_roles'][$role] = trans('csv.column_' . $role);
        }

        $config['column-count']   = $data['columnCount'];
        $this->job->configuration = $config;
        $this->job->save();

        return $data;
    }
}