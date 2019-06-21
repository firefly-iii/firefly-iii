<?php
/**
 * ConfigureRolesHandler.php
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
 * Class ConfigureRolesHandler
 */
class ConfigureRolesHandler implements FileConfigurationInterface
{
    /** @var AttachmentHelperInterface */
    private $attachments;
    /** @var array */
    private $examples;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var int */
    private $totalColumns;

    /**
     * Verifies that the configuration of the job is actually complete, and valid.
     *
     * @param array $config
     *
     * @return MessageBag
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function configurationComplete(array $config): MessageBag
    {
        /** @var array $roles */
        $roles    = $config['column-roles'];
        $assigned = 0;

        // check if data actually contains amount column (foreign amount does not count)
        $hasAmount        = false;
        $hasForeignAmount = false;
        $hasForeignCode   = false;
        foreach ($roles as $role) {
            if ('_ignore' !== $role) {
                ++$assigned;
            }
            if (in_array($role, ['amount', 'amount_credit', 'amount_debit', 'amount_negated'])) {
                $hasAmount = true;
            }
            if ('foreign-currency-code' === $role) {
                $hasForeignCode = true;
            }
            if ('amount_foreign' === $role) {
                $hasForeignAmount = true;
            }
        }

        // all assigned and correct foreign info
        if ($assigned > 0 && $hasAmount && ($hasForeignCode === $hasForeignAmount)) {
            return new MessageBag;
        }
        if (0 === $assigned || !$hasAmount) {
            $message  = (string)trans('import.job_config_roles_rwarning');
            $messages = new MessageBag();
            $messages->add('error', $message);

            return $messages;
        }

        // warn if has foreign amount but no currency code:
        if ($hasForeignAmount && !$hasForeignCode) {
            $message  = (string)trans('import.job_config_roles_fa_warning');
            $messages = new MessageBag();
            $messages->add('error', $message);

            return $messages;
        }


        return new MessageBag; // @codeCoverageIgnore
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
        $count  = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role                            = $data['role'][$i] ?? '_ignore';
            $mapping                         = (isset($data['map'][$i]) && '1' === $data['map'][$i]);
            $config['column-roles'][$i]      = $role;
            $config['column-do-mapping'][$i] = $mapping;
            Log::debug(sprintf('Column %d has been given role %s (mapping: %s)', $i, $role, var_export($mapping, true)));
        }
        $config   = $this->ignoreUnmappableColumns($config);
        $messages = $this->configurationComplete($config);

        if (0 === $messages->count()) {
            $this->repository->setStage($this->importJob, 'ready_to_run');
            if ($this->isMappingNecessary($config)) {
                $this->repository->setStage($this->importJob, 'map');
            }
            $this->repository->setConfiguration($this->importJob, $config);
        }

        return $messages;
    }

    /**
     * Extracts example data from a single row and store it in the class.
     *
     * @param array $line
     */
    public function getExampleFromLine(array $line): void
    {
        foreach ($line as $column => $value) {
            $value = trim($value);
            if ('' != $value) {
                $this->examples[$column][] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * Return a bunch of examples from the CSV file the user has uploaded.
     *
     * @param Reader $reader
     * @param array  $config
     *
     * @throws FireflyException
     */
    public function getExamplesFromFile(Reader $reader, array $config): void
    {
        $limit  = (int)config('csv.example_rows', 5);
        $offset = isset($config['has-headers']) && true === $config['has-headers'] ? 1 : 0;

        // make statement.
        try {
            $stmt = (new Statement)->limit($limit)->offset($offset);
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException($e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        // grab the records:
        $records = $stmt->process($reader);
        /** @var array $line */
        foreach ($records as $line) {
            $line               = array_values($line);
            $line               = $this->processSpecifics($config, $line);
            $count              = count($line);
            $this->totalColumns = $count > $this->totalColumns ? $count : $this->totalColumns;
            $this->getExampleFromLine($line);
        }
        // save column count:
        $this->saveColumCount();
        $this->makeExamplesUnique();
    }

    /**
     * Get the header row, if one is present.
     *
     * @param Reader $reader
     * @param array  $config
     *
     * @return array
     * @throws FireflyException
     */
    public function getHeaders(Reader $reader, array $config): array
    {
        $headers = [];
        if (isset($config['has-headers']) && true === $config['has-headers']) {
            try {
                $stmt    = (new Statement)->limit(1)->offset(0);
                $records = $stmt->process($reader);
                $headers = $records->fetchOne();
                // @codeCoverageIgnoreStart
            } catch (Exception $e) {
                Log::error($e->getMessage());
                throw new FireflyException($e->getMessage());
            }
            // @codeCoverageIgnoreEnd
            Log::debug('Detected file headers:', $headers);
        }

        return $headers;
    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        try {
            $reader = $this->getReader();
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new FireflyException($e->getMessage());
        }
        // @codeCoverageIgnoreEnd
        $configuration = $this->importJob->configuration;
        $headers       = $this->getHeaders($reader, $configuration);

        // get example rows:
        $this->getExamplesFromFile($reader, $configuration);

        return [
            'examples' => $this->examples,
            'roles'    => $this->getRoles(),
            'total'    => $this->totalColumns,
            'headers'  => $headers,
        ];
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
     * Returns all possible roles and translate their name. Then sort them.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach (array_keys(config('csv.import_roles')) as $role) {
            $roles[$role] = (string)trans('import.column_' . $role);
        }
        asort($roles);

        return $roles;
    }

    /**
     * If the user has checked columns that cannot be mapped to any value, this function will
     * uncheck them and return the configuration again.
     *
     * @param array $config
     *
     * @return array
     */
    public function ignoreUnmappableColumns(array $config): array
    {
        $count = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role    = $config['column-roles'][$i] ?? '_ignore';
            $mapping = $config['column-do-mapping'][$i] ?? false;
            // if the column can be mapped depends on the config:
            $canMap                          = (bool)config(sprintf('csv.import_roles.%s.mappable', $role));
            $mapping                         = $mapping && $canMap;
            $config['column-do-mapping'][$i] = $mapping;
        }

        return $config;
    }

    /**
     * Returns false when it's not necessary to map values. This saves time and is user friendly
     * (will skip to the next screen).
     *
     * @param array $config
     *
     * @return bool
     */
    public function isMappingNecessary(array $config): bool
    {
        /** @var array $doMapping */
        $doMapping  = $config['column-do-mapping'] ?? [];
        $toBeMapped = 0;
        foreach ($doMapping as $doMap) {
            if (true === $doMap) {
                ++$toBeMapped;
            }
        }

        return !(0 === $toBeMapped);
    }

    /**
     * Make sure that the examples do not contain double data values.
     */
    public function makeExamplesUnique(): void
    {
        foreach ($this->examples as $index => $values) {
            $this->examples[$index] = array_unique($values);
        }
    }

    /**
     * if the user has configured specific fixes to be applied, they must be applied to the example data as well.
     *
     * @param array $config
     * @param array $line
     *
     * @return array
     */
    public function processSpecifics(array $config, array $line): array
    {
        $validSpecifics = array_keys(config('csv.import_specifics'));
        $specifics      = $config['specifics'] ?? [];
        $names          = array_keys($specifics);
        foreach ($names as $name) {
            if (!in_array($name, $validSpecifics, true)) {
                continue;
            }
            /** @var SpecificInterface $specific */
            $specific = app('FireflyIII\Import\Specifics\\' . $name);
            $line     = $specific->run($line);
        }

        return $line;

    }

    /**
     * Save the column count in the job. It's used in a later stage.
     *
     * @return void
     */
    public function saveColumCount(): void
    {
        $config                 = $this->importJob->configuration;
        $config['column-count'] = $this->totalColumns;
        $this->repository->setConfiguration($this->importJob, $config);
    }

    /**
     * Set job and some start values.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->attachments  = app(AttachmentHelperInterface::class);
        $this->totalColumns = 0;
        $this->examples     = [];
    }
}
