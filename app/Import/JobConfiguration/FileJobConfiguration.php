<?php
declare(strict_types=1);
/**
 * FileJobConfiguration.php
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

namespace FireflyIII\Import\JobConfiguration;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\File\ConfigurationInterface;
use FireflyIII\Support\Import\Configuration\File\ConfigureMappingHandler;
use FireflyIII\Support\Import\Configuration\File\ConfigureRolesHandler;
use FireflyIII\Support\Import\Configuration\File\ConfigureUploadHandler;
use FireflyIII\Support\Import\Configuration\File\NewFileJobHandler;
use Illuminate\Support\MessageBag;

/**
 * Class FileJobConfiguration
 */
class FileJobConfiguration implements JobConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * ConfiguratorInterface constructor.
     */
    public function __construct()
    {
    }

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        if ($this->importJob->stage === 'ready_to_run') {
            return true;
        }

        return false;
    }

    /**
     * Store any data from the $data array into the job. Anything in the message bag will be flashed
     * as an error to the user, regardless of its content.
     *
     * @param array $data
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function configureJob(array $data): MessageBag
    {
        $configurator = $this->getConfigurationObject();
        $configurator->setJob($this->importJob);

        return $configurator->configureJob($data);
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @throws FireflyException
     * @return array
     */
    public function getNextData(): array
    {
        $configurator = $this->getConfigurationObject();
        $configurator->setJob($this->importJob);

        return $configurator->getNextData();
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @throws FireflyException
     * @return string
     */
    public function getNextView(): string
    {
        switch ($this->importJob->stage) {
            case 'new':
                return 'import.file.new';
            case 'configure-upload':
                return 'import.file.configure-upload';
                break;
            case 'roles':
                return 'import.file.roles';
                break;
            case 'map':
                return 'import.file.map';
                break;
            default:
                // @codeCoverageIgnoreStart
                throw new FireflyException(
                    sprintf('FileJobConfiguration::getNextView() cannot handle stage "%s"', $this->importJob->stage)
                );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job): void
    {
        $this->importJob  = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
    }

    /**
     * Get the configuration handler for this specific stage.
     *
     * @return ConfigurationInterface
     * @throws FireflyException
     */
    private function getConfigurationObject(): ConfigurationInterface
    {
        $class = 'DoNotExist';
        switch ($this->importJob->stage) {
            case 'new': // has nothing, no file upload or anything.
                $class = NewFileJobHandler::class;
                break;
            case 'configure-upload':
                $class = ConfigureUploadHandler::class;
                break;
            case 'roles':
                $class = ConfigureRolesHandler::class;
                break;
            case 'map':
                $class = ConfigureMappingHandler::class;
                break;
            //            case 'upload-config': // has file, needs file config.
            //                $class = UploadConfig::class;
            //                break;
            //            case 'roles': // has configured file, needs roles.
            //                $class = Roles::class;
            //                break;
            //            case 'map': // has roles, needs mapping.
            //                $class = Map::class;
            //                break;
            //            default:
            //                break;
        }
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Class %s does not exist in getConfigurationClass().', $class)); // @codeCoverageIgnore
        }

        return app($class);
    }
}
