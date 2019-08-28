<?php
/**
 * ConfigureMappingHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;

/**
 * Class ConfigureMappingHandler
 */
class ConfigureMappingHandler implements FileConfigurationInterface
{
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var array */
    private $columnConfig;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Apply the users selected specifics on the current row.
     *
     * @param array $config
     * @param array $row
     *
     * @return array
     */
    public function applySpecifics(array $config, array $row): array
    {
        // run specifics here:
        // and this is the point where the specifix go to work.
        $validSpecifics = array_keys(config('csv.import_specifics'));
        $specifics      = $config['specifics'] ?? [];
        $names          = array_keys($specifics);
        foreach ($names as $name) {
            if (!in_array($name, $validSpecifics, true)) {
                continue;
            }
            $class = config(sprintf('csv.import_specifics.%s', $name));
            /** @var SpecificInterface $specific */
            $specific = app($class);

            // it returns the row, possibly modified:
            $row = $specific->run($row);
        }

        return $row;
    }

    /**
     * Store data associated with current stage.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        $config = $this->importJob->configuration;

        if (isset($data['mapping']) && is_array($data['mapping'])) {
            foreach ($data['mapping'] as $index => $array) {
                $config['column-mapping-config'][$index] = [];
                foreach ($array as $value => $mapId) {
                    $mapId = (int)$mapId;
                    if (0 !== $mapId) {
                        $config['column-mapping-config'][$index][(string)$value] = $mapId;
                    }
                }
            }
        }
        $this->repository->setConfiguration($this->importJob, $config);
        $this->repository->setStage($this->importJob, 'ready_to_run');

        return new MessageBag;
    }

    /**
     * Create the "mapper" class that will eventually return the correct data for the user
     * to map against. For example: a list of asset accounts. A list of budgets. A list of tags.
     *
     * @param string $column
     *
     * @return MapperInterface
     * @throws FireflyException
     */
    public function createMapper(string $column): MapperInterface
    {
        $mapperClass = config('csv.import_roles.' . $column . '.mapper');
        $mapperName  = sprintf('FireflyIII\\Import\Mapper\\%s', $mapperClass);
        if (!class_exists($mapperName)) {
            throw new FireflyException(sprintf('Class "%s" does not exist. Cannot map "%s"', $mapperName, $column)); // @codeCoverageIgnore
        }

        return app($mapperName);
    }

    /**
     * For each column in the configuration of the job, will:
     * - validate the role.
     * - validate if it can be used for mapping
     * - if so, create an entry in $columnConfig
     *
     * @param array $config
     *
     * @return array the column configuration.
     * @throws FireflyException
     */
    public function doColumnConfig(array $config): array
    {
        /** @var array $requestMapping */
        $requestMapping = $config['column-do-mapping'] ?? [];
        $columnConfig   = [];
        /**
         * @var int
         * @var bool $mustBeMapped
         */
        foreach ($requestMapping as $index => $requested) {
            // sanitize column name, so we're sure it's valid.
            $column    = $this->sanitizeColumnName($config['column-roles'][$index] ?? '_ignore');
            $doMapping = $this->doMapOfColumn($column, $requested);
            if ($doMapping) {
                // user want to map this column. And this is possible.
                $columnConfig[$index] = [
                    'name'          => $column,
                    'options'       => $this->createMapper($column)->getMap(),
                    'preProcessMap' => $this->getPreProcessorName($column),
                    'values'        => [],
                ];
            }
        }

        return $columnConfig;
    }

    /**
     * For each $name given, and if the user wants to map the column, will return
     * true when the column can also be mapped.
     *
     * Unmappable columns will always return false.
     * Mappable columns will return $requested.
     *
     * @param string $name
     * @param bool   $requested
     *
     * @return bool
     */
    public function doMapOfColumn(string $name, bool $requested): bool
    {
        $canBeMapped = config('csv.import_roles.' . $name . '.mappable');

        return true === $canBeMapped && true === $requested;
    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        $config       = $this->importJob->configuration;
        $columnConfig = $this->doColumnConfig($config);

        // in order to actually map we also need to read the FULL file.
        try {
            $reader = $this->getReader();
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        // get ALL values for the mappable columns from the CSV file:
        $columnConfig = $this->getValuesForMapping($reader, $config, $columnConfig);

        return $columnConfig;
    }

    /**
     * Will return the name of the pre-processor: a special class that will clean up any input that may be found
     * in the users input (aka the file uploaded). Only two examples exist at this time: a space or comma separated
     * list of tags.
     *
     * @param string $column
     *
     * @return string
     */
    public function getPreProcessorName(string $column): string
    {
        $name            = '';
        $hasPreProcess   = config(sprintf('csv.import_roles.%s.pre-process-map', $column));
        $preProcessClass = config(sprintf('csv.import_roles.%s.pre-process-mapper', $column));

        if (null !== $hasPreProcess && true === $hasPreProcess && null !== $preProcessClass) {
            $name = sprintf('FireflyIII\\Import\\MapperPreProcess\\%s', $preProcessClass);
        }

        return $name;
    }

    /**
     * Return an instance of a CSV file reader so content of the file can be read.
     *
     * @throws \League\Csv\Exception
     */
    public function getReader(): Reader
    {
        $content = '';
        /** @var Collection $collection */
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
        $reader->setDelimiter($config['delimiter']);

        return $reader;
    }

    /**
     * Read the CSV file. For each row, check for each column:
     *
     * - If it can be mapped. And if so,
     * - Run the pre-processor
     * - Add the value to the list of "values" that the user must map.
     *
     * @param Reader $reader
     * @param array  $config
     * @param array  $columnConfig
     *
     * @return array
     * @throws FireflyException
     *
     */
    public function getValuesForMapping(Reader $reader, array $config, array $columnConfig): array
    {
        $offset = isset($config['has-headers']) && true === $config['has-headers'] ? 1 : 0;
        try {
            $stmt = (new Statement)->offset($offset);
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            throw new FireflyException(sprintf('Could not create reader: %s', $e->getMessage()));
        }
        // @codeCoverageIgnoreEnd
        $results         = $stmt->process($reader);
        $mappableColumns = array_keys($columnConfig); // the actually columns that can be mapped.
        foreach ($results as $lineIndex => $line) {
            Log::debug(sprintf('Trying to collect values for line #%d', $lineIndex));
            $line = $this->applySpecifics($config, $line);

            /** @var int $columnIndex */
            foreach ($mappableColumns as $columnIndex) { // this is simply 1, 2, 3, etc.
                if (!isset($line[$columnIndex])) {
                    // don't need to handle this. Continue.
                    continue;
                }
                $value = trim($line[$columnIndex]);
                if ('' === $value) {
                    // value is empty, ignore it.
                    continue;
                }
                $columnConfig[$columnIndex]['values'][] = $value;
            }
        }

        // loop array again. This time, do uniqueness.
        // and remove arrays that have 0 values.
        foreach ($mappableColumns as $columnIndex) {
            $columnConfig[$columnIndex]['values'] = array_unique($columnConfig[$columnIndex]['values']);
            asort($columnConfig[$columnIndex]['values']);
            // if the count of this array is zero, there is nothing to map.
            if (0 === count($columnConfig[$columnIndex]['values'])) {
                unset($columnConfig[$columnIndex]); // @codeCoverageIgnore
            }
        }

        return $columnConfig;
    }

    /**
     * For each given column name, will return either the name (when it's a valid one)
     * or return the _ignore column.
     *
     * @param string $name
     *
     * @return string
     */
    public function sanitizeColumnName(string $name): string
    {
        /** @var array $validColumns */
        $validColumns = array_keys(config('csv.import_roles'));
        if (!in_array($name, $validColumns, true)) {
            $name = '_ignore';
        }

        return $name;
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->attachments  = app(AttachmentHelperInterface::class);
        $this->columnConfig = [];
    }
}
