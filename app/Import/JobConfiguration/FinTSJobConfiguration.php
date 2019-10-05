<?php
/**
 * FinTSJobConfiguration.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\JobConfiguration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\JobConfiguration\FinTS\ChooseAccountHandler;
use FireflyIII\Support\Import\JobConfiguration\FinTS\FinTSConfigurationInterface;
use FireflyIII\Support\Import\JobConfiguration\FinTS\NewFinTSJobHandler;
use Illuminate\Support\MessageBag;

/**
 *
 * Class FinTSJobConfiguration
 */
class FinTSJobConfiguration implements JobConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;

    /**
     * Returns true when the initial configuration for this job is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        return $this->importJob->stage === FinTSConfigurationSteps::GO_FOR_IMPORT;
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
        return $this->getConfigurationObject()->configureJob($data);
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        return $this->getConfigurationObject()->getNextData();
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     * @throws FireflyException
     */
    public function getNextView(): string
    {
        switch ($this->importJob->stage) {
            case FinTSConfigurationSteps::NEW:
            case FinTSConfigurationSteps::CHOOSE_ACCOUNT:
                return 'import.fints.' . $this->importJob->stage;
                break;
            default:
                // @codeCoverageIgnoreStart
                throw new FireflyException(
                    sprintf('FinTSJobConfiguration::getNextView() cannot handle stage "%s"', $this->importJob->stage)
                );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
    }

    /**
     * Get the configuration handler for this specific stage.
     *
     * @return FinTSConfigurationInterface
     * @throws FireflyException
     */
    private function getConfigurationObject(): FinTSConfigurationInterface
    {
        $class = 'DoNotExist';
        switch ($this->importJob->stage) {
            case FinTSConfigurationSteps::NEW:
                $class = NewFinTSJobHandler::class;
                break;
            case FinTSConfigurationSteps::CHOOSE_ACCOUNT:
                $class = ChooseAccountHandler::class;
                break;
        }
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Class %s does not exist in getConfigurationClass().', $class)); // @codeCoverageIgnore
        }

        $configurator = app($class);
        $configurator->setImportJob($this->importJob);

        return $configurator;
    }


}
