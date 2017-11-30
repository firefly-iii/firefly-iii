<?php
/**
 * CsvConfigurator.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Configurator;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use FireflyIII\Support\Import\Configuration\Csv\Initial;
use FireflyIII\Support\Import\Configuration\Csv\Map;
use FireflyIII\Support\Import\Configuration\Csv\Roles;
use Log;

/**
 * Class CsvConfigurator.
 */
class CsvConfigurator implements ConfiguratorInterface
{
    /** @var ImportJob */
    private $job;

    /** @var string */
    private $warning = '';

    /**
     * ConfiguratorInterface constructor.
     */
    public function __construct()
    {
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
        if (!$this->job->configuration['initial-config-complete']) {
            return 'import.csv.initial';
        }
        if (!$this->job->configuration['column-roles-complete']) {
            return 'import.csv.roles';
        }
        if (!$this->job->configuration['column-mapping-complete']) {
            return 'import.csv.map';
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
        $config['initial-config-complete'] = $config['initial-config-complete'] ?? false;
        $config['column-roles-complete']   = $config['column-roles-complete'] ?? false;
        $config['column-mapping-complete'] = $config['column-mapping-complete'] ?? false;
        $this->job->configuration          = $config;
        $this->job->save();

        if ($this->job->configuration['initial-config-complete']
            && $this->job->configuration['column-roles-complete']
            && $this->job->configuration['column-mapping-complete']
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
        if (null === $this->job->configuration || 0 === count($this->job->configuration)) {
            Log::debug(sprintf('Gave import job %s initial configuration.', $this->job->key));
            $this->job->configuration = config('csv.default_config');
            $this->job->save();
        }
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
            case !$this->job->configuration['initial-config-complete']:
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

        return $class;
    }
}
