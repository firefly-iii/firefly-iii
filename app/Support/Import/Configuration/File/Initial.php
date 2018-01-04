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

    /** @var string */
    private $warning = '';

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
        $this->job = $job;

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
        /** @var ImportJobRepositoryInterface $repository */
        $repository          = app(ImportJobRepositoryInterface::class);
        $type                = $data['import_file_type'] ?? 'unknown';
        $config              = $this->job->configuration;
        $config['file-type'] = in_array($type, config('import.options.file.import_formats')) ? $type : 'unknown';
        $repository->setConfiguration($this->job, $config);
        $uploaded = $repository->processFile($this->job, $data['import_file'] ?? null);
        $this->job->save();
        Log::debug(sprintf('Result of upload is %s', var_export($uploaded, true)));
        // process config, if present:
        if (isset($data['configuration_file'])) {
            $repository->processConfiguration($this->job, $data['configuration_file']);
        }
        $config                    = $this->job->configuration;
        $config['has-file-upload'] = $uploaded;
        $repository->setConfiguration($this->job, $config);

        if (false === $uploaded) {
            $this->warning = 'No valid upload.';
        }

        return true;
    }
}
