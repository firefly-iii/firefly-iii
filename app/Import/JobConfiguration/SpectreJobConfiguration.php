<?php
/**
 * SpectreJobConfiguration.php
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

namespace FireflyIII\Import\JobConfiguration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\Spectre\AuthenticateConfig;
use FireflyIII\Support\Import\JobConfiguration\Spectre\AuthenticatedConfigHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccount;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\NewConfig;
use FireflyIII\Support\Import\JobConfiguration\Spectre\SpectreJobConfig;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class SpectreJobConfiguration
 *
 * @package FireflyIII\Import\JobConfiguration
 */
class SpectreJobConfiguration implements JobConfigurationInterface
{
    /** @var SpectreJobConfig */
    private $handler;
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
        return $this->handler->configurationComplete();
    }

    /**
     * Store any data from the $data array into the job. Anything in the message bag will be flashed
     * as an error to the user, regardless of its content.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        return $this->handler->configureJob($data);
    }

    /**
     * Return the data required for the next step in the job configuration.
     *
     * @return array
     */
    public function getNextData(): array
    {
        return $this->handler->getNextData();
    }

    /**
     * Returns the view of the next step in the job configuration.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return $this->handler->getNextView();
    }

    /**
     * @param ImportJob $importJob
     *
     * @throws FireflyException
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->handler = $this->getHandler();
    }

    /**
     * @return SpectreJobConfig
     * @throws FireflyException
     */
    private function getHandler(): SpectreJobConfig
    {
        Log::debug(sprintf('Now in SpectreJobConfiguration::getHandler() with stage "%s"', $this->importJob->stage));
        $handler = null;
        switch ($this->importJob->stage) {
            case 'new':
                $handler = app(NewConfig::class);
                $handler->setImportJob($this->importJob);
                break;
            case 'authenticate':
                /** @var SpectreJobConfig $handler */
                $handler = app(AuthenticateConfig::class);
                $handler->setImportJob($this->importJob);
                break;
            case 'choose-login':
                /** @var SpectreJobConfig $handler */
                $handler = app(ChooseLoginHandler::class);
                $handler->setImportJob($this->importJob);
                break;
            case 'authenticated':
                /** @var AuthenticatedConfigHandler $handler */
                $handler = app(AuthenticatedConfigHandler::class);
                $handler->setImportJob($this->importJob);
                break;
            case 'choose-account':
                /** @var ChooseAccount $handler */
                $handler = app(ChooseAccount::class);
                $handler->setImportJob($this->importJob);
                break;
            default:
                throw new FireflyException(sprintf('Firefly III cannot create a configuration handler for stage "%s"', $this->importJob->stage));

        }

        return $handler;
    }
}