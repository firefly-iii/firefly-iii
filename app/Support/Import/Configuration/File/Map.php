<?php
/**
 * Map.php
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

namespace FireflyIII\Support\Import\Configuration\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Import\MapperPreProcess\PreProcessorInterface;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;

/**
 * Class Mapping.
 */
class Map implements ConfigurationInterface
{
    /** @var array that holds each column to be mapped by the user */
    private $data = [];
    /** @var ImportJob */
    private $job;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var array */
    private $validSpecifics = [];

    /**
     * @return array
     *
     * @throws FireflyException
     * @throws \League\Csv\Exception
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getData(): array
    {
        $this->getMappableColumns();

        // in order to actually map we also need all possible values from the CSV file.
        $config  = $this->getConfig();
        $content = $this->repository->uploadFileContents($this->job);
        $offset  = 0;
        /** @var Reader $reader */
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);
        if ($config['has-headers']) {
            $offset = 1;
        }
        $stmt                 = (new Statement)->offset($offset);
        $results              = $stmt->process($reader);
        $this->validSpecifics = array_keys(config('csv.import_specifics'));
        $indexes              = array_keys($this->data);
        $rowIndex             = 0;
        foreach ($results as $rowIndex => $row) {
            $row = $this->runSpecifics($row);

            //do something here
            foreach ($indexes as $index) { // this is simply 1, 2, 3, etc.
                if (!isset($row[$index])) {
                    // don't really know how to handle this. Just skip, for now.
                    continue;
                }
                $value = trim($row[$index]);
                if (strlen($value) > 0) {
                    // we can do some preprocessing here,
                    // which is exclusively to fix the tags:
                    if (null !== $this->data[$index]['preProcessMap'] && strlen($this->data[$index]['preProcessMap']) > 0) {
                        /** @var PreProcessorInterface $preProcessor */
                        $preProcessor                 = app($this->data[$index]['preProcessMap']);
                        $result                       = $preProcessor->run($value);
                        $this->data[$index]['values'] = array_merge($this->data[$index]['values'], $result);

                        Log::debug($rowIndex . ':' . $index . 'Value before preprocessor', ['value' => $value]);
                        Log::debug($rowIndex . ':' . $index . 'Value after preprocessor', ['value-new' => $result]);
                        Log::debug($rowIndex . ':' . $index . 'Value after joining', ['value-complete' => $this->data[$index]['values']]);

                        continue;
                    }

                    $this->data[$index]['values'][] = $value;
                }
            }
        }
        $setIndexes = array_keys($this->data);
        foreach ($setIndexes as $index) {
            $this->data[$index]['values'] = array_unique($this->data[$index]['values']);
            asort($this->data[$index]['values']);
            // if the count of this array is zero, there is nothing to map.
            if (count($this->data[$index]['values']) === 0) {
                unset($this->data[$index]);
            }
        }
        unset($setIndexes);

        // save number of rows, thus number of steps, in job:
        $steps                      = $rowIndex * 5;
        $extended                   = $this->job->extended_status;
        $extended['steps']          = $steps;
        $this->job->extended_status = $extended;
        $this->job->save();

        return $this->data;
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return '';
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job): ConfigurationInterface
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

        return $this;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        $config = $this->getConfig();

        if (isset($data['mapping'])) {
            foreach ($data['mapping'] as $index => $data) {
                $config['column-mapping-config'][$index] = [];
                foreach ($data as $value => $mapId) {
                    $mapId = intval($mapId);
                    if (0 !== $mapId) {
                        $config['column-mapping-config'][$index][$value] = intval($mapId);
                    }
                }
            }
        }

        // set thing to be completed.
        $config['stage'] = 'ready';
        $this->saveConfig($config);

        return true;
    }

    /**
     * @param string $column
     *
     * @return MapperInterface
     */
    private function createMapper(string $column): MapperInterface
    {
        $mapperClass = config('csv.import_roles.' . $column . '.mapper');
        $mapperName  = sprintf('\\FireflyIII\\Import\Mapper\\%s', $mapperClass);
        /** @var MapperInterface $mapper */
        $mapper = app($mapperName);

        return $mapper;
    }

    /**
     * Short hand method.
     *
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * @return bool
     *
     * @throws FireflyException
     */
    private function getMappableColumns(): bool
    {
        $config = $this->getConfig();
        /**
         * @var int
         * @var bool $mustBeMapped
         */
        foreach ($config['column-do-mapping'] as $index => $mustBeMapped) {
            $column    = $this->validateColumnName($config['column-roles'][$index] ?? '_ignore');
            $shouldMap = $this->shouldMapColumn($column, $mustBeMapped);
            if ($shouldMap) {
                // create configuration entry for this specific column and add column to $this->data array for later processing.
                $this->data[$index] = [
                    'name'          => $column,
                    'index'         => $index,
                    'options'       => $this->createMapper($column)->getMap(),
                    'preProcessMap' => $this->getPreProcessorName($column),
                    'values'        => [],
                ];
            }
        }

        return true;
    }

    /**
     * @param string $column
     *
     * @return string
     */
    private function getPreProcessorName(string $column): string
    {
        $name            = '';
        $hasPreProcess   = config('csv.import_roles.' . $column . '.pre-process-map');
        $preProcessClass = config('csv.import_roles.' . $column . '.pre-process-mapper');

        if (null !== $hasPreProcess && true === $hasPreProcess && null !== $preProcessClass) {
            $name = sprintf('\\FireflyIII\\Import\\MapperPreProcess\\%s', $preProcessClass);
        }

        return $name;
    }

    /**
     * @param array $row
     *
     * @return array
     *
     * @throws FireflyException
     */
    private function runSpecifics(array $row): array
    {
        // run specifics here:
        // and this is the point where the specifix go to work.
        $config    = $this->getConfig();
        $specifics = $config['specifics'] ?? [];
        $names     = array_keys($specifics);
        foreach ($names as $name) {
            if (!in_array($name, $this->validSpecifics)) {
                throw new FireflyException(sprintf('"%s" is not a valid class name', $name));
            }
            $class = config('csv.import_specifics.' . $name);
            /** @var SpecificInterface $specific */
            $specific = app($class);

            // it returns the row, possibly modified:
            $row = $specific->run($row);
        }

        return $row;
    }

    /**
     * @param array $array
     */
    private function saveConfig(array $array)
    {
        $this->repository->setConfiguration($this->job, $array);
    }

    /**
     * @param string $column
     * @param bool   $mustBeMapped
     *
     * @return bool
     */
    private function shouldMapColumn(string $column, bool $mustBeMapped): bool
    {
        $canBeMapped = config('csv.import_roles.' . $column . '.mappable');

        return $canBeMapped && $mustBeMapped;
    }

    /**
     * @param string $column
     *
     * @return string
     *
     * @throws FireflyException
     */
    private function validateColumnName(string $column): string
    {
        // is valid column?
        $validColumns = array_keys(config('csv.import_roles'));
        if (!in_array($column, $validColumns)) {
            throw new FireflyException(sprintf('"%s" is not a valid column.', $column));
        }

        return $column;
    }
}
