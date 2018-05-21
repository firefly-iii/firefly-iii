<?php
/**
 * AuthenticatedConfigHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class AuthenticatedHandler
 */
class AuthenticatedHandler implements SpectreConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('AuthenticatedConfigHandler::configurationComplete() always returns true');

        return true;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('AuthenticatedConfigHandler::configurationComplete() always returns empty message bag');

        return new MessageBag();
    }

    /**
     * Get data for config view.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('AuthenticatedConfigHandler::getNextData() always returns []');

        return [];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('AuthenticatedConfigHandler::getNextView() always returns ""');

        return '';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}