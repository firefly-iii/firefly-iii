<?php
/**
 * BunqJobConfiguration.php
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
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\Bunq\BunqJobConfigurationInterface;
use FireflyIII\Support\Import\JobConfiguration\Bunq\ChooseAccountsHandler;
use FireflyIII\Support\Import\JobConfiguration\Bunq\NewBunqJobHandler;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class BunqJobConfiguration
 */
class BunqJobConfiguration implements JobConfigurationInterface
{
    /** @var BunqJobConfigurationInterface Bunq job interface */
    private $handler;
    /** @var ImportJob The import job */
    private $importJob;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;

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
     * Set import job.
     *
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
     * Get correct handler.
     *
     * @return BunqJobConfigurationInterface
     * @throws FireflyException
     */
    private function getHandler(): BunqJobConfigurationInterface
    {
        Log::debug(sprintf('Now in BunqJobConfiguration::getHandler() with stage "%s"', $this->importJob->stage));
        $handler = null;
        switch ($this->importJob->stage) {
            case 'new':
                $handler = app(NewBunqJobHandler::class);
                $handler->setImportJob($this->importJob);
                break;
            case 'choose-accounts':
                /** @var ChooseAccountsHandler $handler */
                $handler = app(ChooseAccountsHandler::class);
                $handler->setImportJob($this->importJob);
                break;
            default:
                // @codeCoverageIgnoreStart
                throw new FireflyException(sprintf('Firefly III cannot create a configuration handler for stage "%s"', $this->importJob->stage));
            // @codeCoverageIgnoreEnd
        }

        return $handler;
    }
}
