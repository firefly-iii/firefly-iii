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

namespace FireflyIII\Support\Import\Configuration\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Mapper\MapperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use League\Csv\Exception;
use League\Csv\Reader;
use Log;

/**
 * Class ConfigureMappingHandler
 */
class ConfigureMappingHandler implements ConfigurationInterface
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
     * Store data associated with current stage.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        return new MessageBag;
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
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException('Cannot get reader: ' . $e->getMessage());
        }
        //
        //        if ($config['has-headers']) {
        //            $offset = 1;
        //        }
        //        $stmt                 = (new Statement)->offset($offset);
        //        $results              = $stmt->process($reader);
        //        $this->validSpecifics = array_keys(config('csv.import_specifics'));
        //        $indexes              = array_keys($this->data);
        //        $rowIndex             = 0;
        //        foreach ($results as $rowIndex => $row) {
        //            $row = $this->runSpecifics($row);
        //
        //            //do something here
        //            foreach ($indexes as $index) { // this is simply 1, 2, 3, etc.
        //                if (!isset($row[$index])) {
        //                    // don't really know how to handle this. Just skip, for now.
        //                    continue;
        //                }
        //                $value = trim($row[$index]);
        //                if (\strlen($value) > 0) {
        //                    // we can do some preprocessing here,
        //                    // which is exclusively to fix the tags:
        //                    if (null !== $this->data[$index]['preProcessMap'] && \strlen($this->data[$index]['preProcessMap']) > 0) {
        //                        /** @var PreProcessorInterface $preProcessor */
        //                        $preProcessor                 = app($this->data[$index]['preProcessMap']);
        //                        $result                       = $preProcessor->run($value);
        //                        $this->data[$index]['values'] = array_merge($this->data[$index]['values'], $result);
        //
        //                        Log::debug($rowIndex . ':' . $index . 'Value before preprocessor', ['value' => $value]);
        //                        Log::debug($rowIndex . ':' . $index . 'Value after preprocessor', ['value-new' => $result]);
        //                        Log::debug($rowIndex . ':' . $index . 'Value after joining', ['value-complete' => $this->data[$index]['values']]);
        //
        //                        continue;
        //                    }
        //
        //                    $this->data[$index]['values'][] = $value;
        //                }
        //            }
        //        }
        //        $setIndexes = array_keys($this->data);
        //        foreach ($setIndexes as $index) {
        //            $this->data[$index]['values'] = array_unique($this->data[$index]['values']);
        //            asort($this->data[$index]['values']);
        //            // if the count of this array is zero, there is nothing to map.
        //            if (\count($this->data[$index]['values']) === 0) {
        //                unset($this->data[$index]);
        //            }
        //        }
        //        unset($setIndexes);
        //
        //        // save number of rows, thus number of steps, in job:
        //        $steps                      = $rowIndex * 5;
        //        $extended                   = $this->job->extended_status;
        //        $extended['steps']          = $steps;
        //        $this->job->extended_status = $extended;
        //        $this->job->save();
        //
        //        return $this->data;
        //         */
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob  = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
        $this->attachments  = app(AttachmentHelperInterface::class);
        $this->columnConfig = [];
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
    private function createMapper(string $column): MapperInterface
    {
        $mapperClass = config('csv.import_roles.' . $column . '.mapper');
        $mapperName  = sprintf('\\FireflyIII\\Import\Mapper\\%s', $mapperClass);
        if (!class_exists($mapperName)) {
            throw new FireflyException(sprintf('Class "%s" does not exist. Cannot map "%s"', $mapperName, $column));
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
    private function doColumnConfig(array $config): array
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
    private function doMapOfColumn(string $name, bool $requested): bool
    {
        $canBeMapped = config('csv.import_roles.' . $name . '.mappable');

        return $canBeMapped && $requested;
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
    private function getPreProcessorName(string $column): string
    {
        $name            = '';
        $hasPreProcess   = config(sprintf('csv.import_roles.%s.pre-process-map', $column));
        $preProcessClass = config(sprintf('csv.import_roles.%s.pre-process-mapper', $column));

        if (null !== $hasPreProcess && true === $hasPreProcess && null !== $preProcessClass) {
            $name = sprintf('\\FireflyIII\\Import\\MapperPreProcess\\%s', $preProcessClass);
        }

        return $name;
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
     * For each given column name, will return either the name (when it's a valid one)
     * or return the _ignore column.
     *
     * @param string $name
     *
     * @return string
     */
    private function sanitizeColumnName(string $name): string
    {
        /** @var array $validColumns */
        $validColumns = array_keys(config('csv.import_roles'));
        if (!\in_array($name, $validColumns, true)) {
            $name = '_ignore';
        }

        return $name;
    }
}