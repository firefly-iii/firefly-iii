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
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use FireflyIII\Support\Import\Configuration\File\Initial;
use FireflyIII\Support\Import\Configuration\File\Map;
use FireflyIII\Support\Import\Configuration\File\Roles;
use FireflyIII\Support\Import\Configuration\File\UploadConfig;
use Log;

/**
 * Class FileConfigurator.
 */
class FileConfigurator implements ConfiguratorInterface
{
    /** @var array */
    private $defaultConfig
        = [
            'stage'                 => 'initial',
            'has-headers'           => false, // assume
            'date-format'           => 'Ymd', // assume
            'delimiter'             => ',', // assume
            'import-account'        => 0, // none,
            'specifics'             => [], // none
            'column-count'          => 0, // unknown
            'column-roles'          => [], // unknown
            'column-do-mapping'     => [], // not yet set which columns must be mapped
            'column-mapping-config' => [], // no mapping made yet.
            'file-type'             => 'csv', // assume
            'has-config-file'       => true,
            'apply-rules'           => true,
            'match-bills'           => false,
            'auto-start'            => false,
        ];
    /** @var ImportJob */
    private $job;
    /** @var ImportJobRepositoryInterface */
    private $repository;


    // give job default config:
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
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call configureJob() without a job.');
        }
        /** @var ConfigurationInterface $object */
        $object = app($this->getConfigurationClass());
        $object->setJob($this->job);
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
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call getNextData() without a job.');
        }
        /** @var ConfigurationInterface $object */
        $object = app($this->getConfigurationClass());
        $object->setJob($this->job);

        return $object->getData();
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call getNextView() without a job.');
        }
        $config = $this->getConfig();
        $stage  = $config['stage'] ?? 'initial';
        switch ($stage) {
            case 'initial': // has nothing, no file upload or anything.
                return 'import.file.initial';
            case 'upload-config': // has file, needs file config.
                return 'import.file.upload-config';
            case 'roles': // has configured file, needs roles.
                return 'import.file.roles';
            case 'map': // has roles, needs mapping.
                return 'import.file.map';
        }
        throw new FireflyException(sprintf('No view for stage "%s"', $stage));
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     * @throws FireflyException
     */
    public function getWarningMessage(): string
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call getWarningMessage() without a job.');
        }

        return $this->warning;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function isJobConfigured(): bool
    {
        if (is_null($this->job)) {
            throw new FireflyException('Cannot call isJobConfigured() without a job.');
        }
        $config = $this->getConfig();
        $stage  = $config['stage'] ?? 'initial';
        if ($stage === 'ready') {
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
        Log::debug(sprintf('FileConfigurator::setJob(#%d: %s)', $job->id, $job->key));
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

        // set number of steps to 100:
        $extendedStatus          = $this->getExtendedStatus();
        $extendedStatus['steps'] = 6;
        $extendedStatus['done']  = 0;
        $this->setExtendedStatus($extendedStatus);

        $config    = $this->getConfig();
        $newConfig = array_merge($this->defaultConfig, $config);
        $this->repository->setConfiguration($job, $newConfig);
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
     * @return string
     *
     * @throws FireflyException
     */
    private function getConfigurationClass(): string
    {
        $config = $this->getConfig();
        $stage  = $config['stage'] ?? 'initial';
        $class  = false;
        Log::debug(sprintf('Now in getConfigurationClass() for stage "%s"', $stage));

        switch ($stage) {
            case 'initial': // has nothing, no file upload or anything.
                $class = Initial::class;
                break;
            case 'upload-config': // has file, needs file config.
                $class = UploadConfig::class;
                break;
            case 'roles': // has configured file, needs roles.
                $class = Roles::class;
                break;
            case 'map': // has roles, needs mapping.
                $class = Map::class;
                break;
            default:
                break;
        }

        if (false === $class || 0 === strlen($class)) {
            throw new FireflyException(sprintf('Cannot handle job stage "%s" in getConfigurationClass().', $stage));
        }
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Class %s does not exist in getConfigurationClass().', $class)); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Configuration class is "%s"', $class));

        return $class;
    }

    /**
     * Shorthand method to return the extended status.
     *
     * @codeCoverageIgnore
     * @return array
     */
    private function getExtendedStatus(): array
    {
        return $this->repository->getExtendedStatus($this->job);
    }

    /**
     * Shorthand method to set the extended status.
     *
     * @codeCoverageIgnore
     *
     * @param array $extended
     */
    private function setExtendedStatus(array $extended): void
    {
        $this->repository->setExtendedStatus($this->job, $extended);

        return;
    }
}
