<?php
/**
 * FileConfigurator.php
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

namespace FireflyIII\Import\Configuration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use FireflyIII\Support\Import\Configuration\File\Initial;
use FireflyIII\Support\Import\Configuration\File\Map;
use FireflyIII\Support\Import\Configuration\File\Roles;
use FireflyIII\Support\Import\Configuration\File\Upload;
use Log;

/**
 * Class FileConfigurator.
 */
class FileConfigurator implements ConfiguratorInterface
{
    /** @var ImportJob */
    private $job;

    /** @var string */
    private $warning = '';

    /**
     * FileConfigurator constructor.
     */
    public function __construct()
    {
        Log::debug('Created FileConfigurator');
    }

    /**
     * Store any data from the $data array into the job.
     *
     * @param array $data
     *
     * @return bool
     *
     * @throws FireflyException
     */
    public function configureJob(array $data): bool
    {
        $class = $this->getConfigurationClass();
        $job   = $this->job;
        /** @var ConfigurationInterface $object */
        $object = new $class($this->job);
        $object->setJob($job);
        $result        = $object->storeConfiguration($data);
        $this->warning = $object->getWarningMessage();

        return $result;
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     *
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        $class = $this->getConfigurationClass();
        $job   = $this->job;
        /** @var ConfigurationInterface $object */
        $object = app($class);
        $object->setJob($job);

        return $object->getData();
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        if (!$this->job->configuration['has-file-upload']) {
            return 'import.file.upload';
        }
        if (!$this->job->configuration['initial-config-complete']) {
            return 'import.file.initial';
        }
        if (!$this->job->configuration['column-roles-complete']) {
            return 'import.file.roles';
        }
        if (!$this->job->configuration['column-mapping-complete']) {
            return 'import.file.map';
        }

        throw new FireflyException('No view for state');
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
     * @return bool
     */
    public function isJobConfigured(): bool
    {
        $config                            = $this->job->configuration;
        $config['has-file-upload']         = $config['has-file-upload'] ?? false;
        $config['initial-config-complete'] = $config['initial-config-complete'] ?? false;
        $config['column-roles-complete']   = $config['column-roles-complete'] ?? false;
        $config['column-mapping-complete'] = $config['column-mapping-complete'] ?? false;
        $this->job->configuration          = $config;
        $this->job->save();

        if ($config['initial-config-complete']
            && $config['column-roles-complete']
            && $config['column-mapping-complete']
            && $config['has-file-upload']
        ) {
            Log::debug('isJobConfigured returns true');

            return true;
        }
        Log::debug('isJobConfigured returns false');

        return false;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
        // give job default config:
        $defaultConfig            = [
            'initial-config-complete' => false,
            'has-headers'             => false, // assume
            'date-format'             => 'Ymd', // assume
            'delimiter'               => ',', // assume
            'import-account'          => 0, // none,
            'specifics'               => [], // none
            'column-count'            => 0, // unknown
            'column-roles'            => [], // unknown
            'column-do-mapping'       => [], // not yet set which columns must be mapped
            'column-roles-complete'   => false, // not yet configured roles for columns
            'column-mapping-config'   => [], // no mapping made yet.
            'column-mapping-complete' => false, // so mapping is not complete.
            'has-config-file'         => true,
            'apply-rules'             => true,
            'match-bills'             => false,
            'auto-start'              => false,
        ];
        $config                   = $this->job->configuration ?? [];
        $finalConfig              = array_merge($defaultConfig, $config);
        $this->job->configuration = $finalConfig;
        $this->job->save();
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    private function getConfigurationClass(): string
    {

        $class = false;
        switch (true) {
            case !$this->job->configuration['has-file-upload']:
                $class = Upload::class;
                break;
            case !$this->job->configuration['initial-config-complete']:
                Log::debug(sprintf('Class is %s', Initial::class));
                Log::debug(sprintf('initial-config-complete is %s', var_export($this->job->configuration['initial-config-complete'], true)));
                $class = Initial::class;
                break;
            case !$this->job->configuration['column-roles-complete']:
                $class = Roles::class;
                break;
            case !$this->job->configuration['column-mapping-complete']:
                $class = Map::class;
                break;
            default:
                break;
        }

        if (false === $class || 0 === strlen($class)) {
            throw new FireflyException('Cannot handle current job state in getConfigurationClass().');
        }
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Class %s does not exist in getConfigurationClass().', $class));
        }
        Log::debug(sprintf('Configuration class is "%s"', $class));

        return $class;
    }
}
