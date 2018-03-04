<?php
/**
 * Initial.php
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

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use Log;

/**
 * Class Initial.
 */
class Initial implements ConfigurationInterface
{
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /** @var string */
    private $warning = '';

    public function __construct()
    {
        Log::debug('Constructed Initial.');
    }

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getData(): array
    {
        $importFileTypes   = [];
        $defaultImportType = config('import.options.file.default_import_format');

        foreach (config('import.options.file.import_formats') as $type) {
            $importFileTypes[$type] = trans('import.import_file_type_' . $type);
        }

        return [
            'default_type' => $defaultImportType,
            'file_types'   => $importFileTypes,
        ];
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return $this->warning;
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
        Log::debug('Now in storeConfiguration for file Upload.');
        $config              = $this->getConfig();
        $type                = $data['import_file_type'] ?? 'csv'; // assume it's a CSV file.
        $config['file-type'] = in_array($type, config('import.options.file.import_formats')) ? $type : 'csv';

        // update config:
        $this->repository->setConfiguration($this->job, $config);

        // make repository process file:
        $uploaded = $this->repository->processFile($this->job, $data['import_file'] ?? null);
        Log::debug(sprintf('Result of upload is %s', var_export($uploaded, true)));

        // process config, if present:
        if (isset($data['configuration_file'])) {
            Log::debug('Will also upload configuration.');
            $this->repository->processConfiguration($this->job, $data['configuration_file']);
        }

        if (false === $uploaded) {
            $this->warning = 'No valid upload.';

            return true;
        }

        // if file was upload safely, assume we can go to the next stage:
        $config          = $this->getConfig();
        $config['stage'] = 'upload-config';
        $this->repository->setConfiguration($this->job, $config);

        return true;
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
}
